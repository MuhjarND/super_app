<?php

namespace App\Services\ProgressZi;

use App\ZiArea;
use App\ZiEvidenceGuide;
use App\ZiGuidelinePoint;
use App\ZiGuidelineSubPoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ZiEvidenceGuideService
{
    protected $document;
    protected $entries;

    public function document()
    {
        if ($this->document === null) {
            $this->document = json_decode(file_get_contents(resource_path('data/progress-zi/evidence-guide.json')), true, 512, JSON_THROW_ON_ERROR);
        }
        return $this->document;
    }

    public function entries()
    {
        if ($this->entries === null) {
            $this->entries = [];
            foreach ($this->document()['areas'] as $area) {
                foreach ($area['points'] as $point) {
                    foreach ($point['entries'] as $entry) {
                        $this->entries[$entry['key']] = $entry + [
                            'notes' => $entry['guidance'],
                            'area_title' => $area['title'], 'group' => $area['group'],
                            'area_guidance' => $area['guidance'], 'point_title' => $point['title'], 'point_code' => $point['code'],
                            'point_guidance' => $point['guidance'],
                        ];
                    }
                }
            }
            if (Schema::hasTable('zi_evidence_guides')) {
                foreach (ZiEvidenceGuide::all() as $stored) {
                    $row = $stored->toArray();
                    $this->entries[$stored->guide_key] = $row + [
                        'key' => $stored->guide_key, 'code' => $stored->display_code,
                        'group' => $stored->group_type, 'guidance' => $stored->notes ?: '',
                    ];
                }
            }
        }
        return $this->entries;
    }

    public function forSubPoint(ZiGuidelineSubPoint $subPoint)
    {
        return $this->entries()[$subPoint->evidence_guide_key] ?? null;
    }

    public function sync()
    {
        return DB::transaction(function () {
            $persistGuides = Schema::hasTable('zi_evidence_guides');
            $counts = ['areas' => 0, 'points' => 0, 'evidence_points' => 0];
            foreach ($this->document()['areas'] as $definition) {
                $area = $this->matchArea($definition);
                if (!$area) {
                    $area = ZiArea::create([
                        'code' => 'PANDUAN-' . strtoupper(substr($definition['group'], 0, 1)) . '-' . $definition['code'],
                        'group_type' => $definition['group'], 'name' => $definition['title'],
                        'description' => $definition['guidance'] ?: null, 'is_active' => true,
                    ]);
                }
                $counts['areas']++;
                foreach ($definition['points'] as $pointDefinition) {
                    $point = $this->matchPoint($area, $pointDefinition);
                    if (!$point) {
                        $point = $area->guidelinePoints()->create([
                            'code' => $this->availableCode($area->guidelinePoints(), $pointDefinition['code'], $pointDefinition['source_row']),
                            'title' => $pointDefinition['title'], 'description' => $pointDefinition['guidance'] ?: null,
                            'sort_order' => $pointDefinition['source_row'],
                        ]);
                    }
                    $point->update(['sort_order' => $pointDefinition['source_row']]);
                    $counts['points']++;
                    foreach ($pointDefinition['entries'] as $entry) {
                        $subPoint = ZiGuidelineSubPoint::where('evidence_guide_key', $entry['key'])->first();
                        if (!$subPoint) {
                            $matches = $point->subPoints()->get()->filter(function ($candidate) use ($entry) {
                                return !$candidate->evidence_guide_key && $this->normalize($candidate->title) === $this->normalize($entry['title']);
                            });
                            $subPoint = $matches->count() === 1 ? $matches->first() : null;
                        }
                        if (!$subPoint) {
                            $subPoint = $point->subPoints()->create([
                                'code' => $this->availableCode($point->subPoints(), $entry['code'], $entry['source_row']),
                                'title' => $entry['title'], 'sort_order' => $entry['source_row'],
                                'evidence_guide_key' => $entry['key'],
                                'description' => $entry['guidance'] ?: null,
                            ]);
                        } else {
                            $subPoint->update(['evidence_guide_key' => $entry['key'], 'sort_order' => $entry['source_row']]);
                            if (($subPoint->description === null || $subPoint->description === '') && $entry['guidance']) {
                                $subPoint->update(['description' => $entry['guidance']]);
                            }
                        }
                        if ($persistGuides) {
                            ZiEvidenceGuide::updateOrCreate(['guide_key' => $entry['key']], $this->databasePayload($definition, $pointDefinition, $entry));
                        }
                        $counts['evidence_points']++;
                    }
                }
            }
            $this->entries = null;
            return $counts;
        });
    }

    public function databasePayload(array $area, array $point, array $entry)
    {
        $source = $this->document();
        return [
            'source_name' => $source['source_name'], 'source_sha256' => $source['source_sha256'],
            'source_sheet' => $source['sheet'], 'source_row' => $entry['source_row'],
            'group_type' => $area['group'], 'area_code' => $area['code'], 'area_title' => $area['title'],
            'area_guidance' => $area['guidance'], 'point_key' => $point['key'],
            'point_code' => $point['code'], 'point_title' => $point['title'], 'point_guidance' => $point['guidance'],
            'display_code' => $entry['code'], 'title' => $entry['title'], 'explanation' => $entry['explanation'],
            'notes' => $entry['guidance'], 'answer_format' => $entry['answer_format'],
            'supporting_data' => $entry['supporting_data'], 'images' => $entry['images'],
        ];
    }

    protected function matchArea(array $definition)
    {
        $keys = collect($definition['points'])->flatMap(function ($point) { return array_column($point['entries'], 'key'); });
        $mapped = ZiArea::where('group_type', $definition['group'])->whereHas('guidelinePoints.subPoints', function ($query) use ($keys) {
            $query->whereIn('evidence_guide_key', $keys);
        })->get();
        if ($mapped->count() === 1) { return $mapped->first(); }
        // Match names within their own group; a numbered legacy/dummy area is not a semantic match.
        $title = $this->normalizeArea($definition['title']);
        $matches = ZiArea::where('group_type', $definition['group'])->get()->filter(function ($candidate) use ($title) {
            return $this->normalizeArea($candidate->name) === $title;
        });
        if ($matches->count() === 1) { return $matches->first(); }
        return ZiArea::where('group_type', $definition['group'])->where('code', 'PANDUAN-' . strtoupper(substr($definition['group'], 0, 1)) . '-' . $definition['code'])->first();
    }

    protected function matchPoint(ZiArea $area, array $definition)
    {
        $points = $area->guidelinePoints()->with('subPoints')->get();
        $mapped = $points->filter(function ($point) use ($definition) {
            return $point->subPoints->whereIn('evidence_guide_key', array_column($definition['entries'], 'key'))->isNotEmpty();
        });
        if ($mapped->count() === 1) { return $mapped->first(); }
        $matches = $points->filter(function ($point) use ($definition) {
            $title = $this->normalize($point->title);
            if ($definition['source_row'] === 8 && in_array($title, ['tim kerja', 'tim reformasi birokrasi'], true)) { return true; }
            return $title === $this->normalize($definition['title']);
        });
        if ($matches->count() === 1) { return $matches->first(); }
        // Reuse an existing point only when its evidence questions identify it unambiguously.
        $titles = array_map(function ($entry) { return $this->normalize($entry['title']); }, $definition['entries']);
        $matches = $points->filter(function ($point) use ($titles) {
            return $point->subPoints->contains(function ($subPoint) use ($titles) {
                return in_array($this->normalize($subPoint->title), $titles, true);
            });
        });
        return $matches->count() === 1 ? $matches->first() : null;
    }

    protected function availableCode($relation, $code, $row)
    {
        if ($code && $code !== '-' && !(clone $relation)->where('code', $code)->exists()) { return $code; }
        $base = 'panduan-' . $row;
        $candidate = $base;
        $suffix = 1;
        while ((clone $relation)->where('code', $candidate)->exists()) { $candidate = $base . '-' . $suffix++; }
        return $candidate;
    }

    protected function normalizeArea($value)
    {
        return preg_replace('/\s+aparatur$/', '', $this->normalize($value));
    }

    protected function normalize($value)
    {
        return trim(preg_replace('/[^a-z0-9]+/u', ' ', mb_strtolower((string) $value, 'UTF-8')));
    }
}
