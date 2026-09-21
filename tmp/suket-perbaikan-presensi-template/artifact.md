# Execution Contract — Surat Keterangan Perbaikan Presensi

## Reference and output
- Reference: `C:\Users\rubik\Documents\suket_perbaikan_presensi (2).docx`
- Reference SHA-256: `71D50FBB3D520B80A746DC6C5A1D03242FCD22F32DC5D85E70AB8D216D7C30E9`
- Output: `C:\xampp\htdocs\super3\public\templates\surat-keterangan-perbaikan-presensi.docx`
- Deliverable count: 1 DOCX template.

## Preservation contract
- Keep the source DOCX as the visual/layout authority.
- Preserve A4 portrait page geometry, margins, paragraph spacing, table border/widths, fonts, and all existing package parts.
- Replace only data-entry/example text with stable double-brace placeholders.
- Keep fixed legal/formal wording and approval labels unchanged.

## Placeholder contract
- `{{nomor_surat}}` is the generated Surat Keluar number.
- Person and attendance values use lowercase snake_case field names to match the application field builder.
- Signature image/signature fields remain explicit placeholders for later rendering.

## Fidelity gates
- Output opens as a valid DOCX.
- All intended fields are present exactly once or in their intended repeated signature positions.
- No old angle-bracket variables or replacement-glyph example text remain.
- Render and inspect every page when a DOCX renderer is available.
