<table border="1">
    <thead>
        <tr>
            <th>Tanggal</th>
            <th>Barang</th>
            <th>Sub Barang</th>
            <th>Nominal</th>
            <th>Deskripsi</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $transaction)
            <tr>
                <td>{{ optional($transaction->transaction_date)->format('d-m-Y') }}</td>
                <td>{{ optional($transaction->item)->name ?: '-' }}</td>
                <td>{{ optional($transaction->detail)->sub_code ?: '-' }}</td>
                <td>{{ $transaction->amount }}</td>
                <td>{{ $transaction->description }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
