<div style="text-align: center; margin-bottom: 15px;">
    <h2 style="font-size: 13pt; margin: 0; padding: 0; font-weight: bold;">REKOD KEHADIRAN KAKITANGAN</h2>
    <h3 style="font-size: 11pt; margin: 4px 0 0 0; padding: 0; font-weight: bold;">{{ strtoupper($selectedUser->name) }} ({{ $selectedUser->staff_id }})</h3>
    <table style="width: 100%; margin-top: 8px; font-size: 9pt;">
        <tr>
            <td style="text-align: left; width: 50%;"><strong>Tahun:</strong> {{ $year }}</td>
            <td style="text-align: right; width: 50%;"><strong>Bulan:</strong> {{ $month }}</td>
        </tr>
    </table>
</div>

<table border="1" cellpadding="4" cellspacing="0" style="width: 100%; font-size: 8.5pt; border-collapse: collapse;">

    <thead>
        <tr style="background-color: #f2f2f2; font-weight: bold; text-align: center;">
            <th style="width: 8%;">HB</th>
            <th style="width: 32%;">Keterangan</th>
            <th style="width: 12%;">Masuk</th>
            <th style="width: 12%;">Keluar</th>
            <th style="width: 12%;">Tempoh</th>
            <th style="width: 12%;">Jumlah</th>
            <th style="width: 12%;">Skor</th>
        </tr>
    </thead>
    <tbody>
        @forelse($reportData as $row)
            @php $sessionCount = count($row['sessions']); @endphp
            @foreach($row['sessions'] as $index => $session)
                <tr>
                    @if($index === 0)
                        <td rowspan="{{ $sessionCount }}" align="center" style="font-weight: bold;">{{ $row['hb'] }}</td>
                        <td rowspan="{{ $sessionCount }}" align="left">{{ $row['keterangan'] }}</td>
                    @endif

                    <td align="center">{{ $session['masuk'] }}</td>
                    <td align="center">{{ $session['keluar'] }}</td>
                    <td align="center">{{ $session['tempoh'] }}</td>

                    @if($index === 0)
                        <td rowspan="{{ $sessionCount }}" align="center" style="font-weight: bold;">{{ $row['jumlah_masa'] }}</td>
                        <td rowspan="{{ $sessionCount }}" align="center" style="font-weight: bold;">{{ $row['skor'] }}</td>
                    @endif
                </tr>
            @endforeach
        @empty
            <tr>
                <td colspan="7" align="center" style="padding: 15px;">Tiada rekod kehadiran untuk staf ini bagi bulan yang dipilih.</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr style="background-color: #f2f2f2; font-weight: bold;">
            <td colspan="5" align="right">Jumlah keseluruhan:</td>
            <td align="center">{{ $grandTotalFormatted }}</td>
            <td align="center">{{ number_format($totalSkor, 1) }}</td>
        </tr>
    </tfoot>
</table>
