<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>បញ្ជីមន្រ្តីរាជការ - អត្តសញ្ញាណប័ណ្ណ</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'khmerossiemreap', 'khmerosmuollight', 'khmeros', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1a56db;
        }
        .header h1 {
            font-family: 'khmerosmuollight', 'khmeros', sans-serif;
            font-size: 16px;
            font-weight: bold;
            color: #1a56db;
            margin-bottom: 4px;
        }
        .header p {
            font-size: 11px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #1a56db;
            color: #fff;
            font-weight: bold;
            padding: 6px 8px;
            text-align: left;
            font-size: 10px;
            border: 1px solid #1a56db;
        }
        td {
            padding: 5px 8px;
            border: 1px solid #ddd;
            font-size: 10px;
            vertical-align: top;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .group-row td {
            background-color: #e8edff;
            font-weight: bold;
            font-size: 11px;
            padding: 5px 8px;
            border: 1px solid #c5d0f0;
        }
        .badge-yes {
            color: #0f5132;
            background-color: #d1e7dd;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }
        .badge-no {
            color: #41464b;
            background-color: #e2e3e5;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }
        .footer {
            margin-top: 15px;
            text-align: right;
            font-size: 9px;
            color: #999;
        }
        .total-info {
            margin-bottom: 8px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>បញ្ជីមន្រ្តីរាជការស៊ីវិល - អត្តសញ្ញាណប័ណ្ណ</h1>
        <p>ចំនួនមន្រ្តីរាជការ៖ {{ $total }} នាក់ | កាលបរិច្ឆេទ៖ {{ now()->format('d/m/Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:40px">ល.រ</th>
                <th>គោត្តនាម និងនាម</th>
                <th style="width:40px">ភេទ</th>
                <th>តួនាទី/មុខតំណែង</th>
                <th>អង្គភាព/អគ្គនាយកដ្ឋាន</th>
                <th>អង្គភាព/នាយកដ្ឋាន</th>
                <th style="width:80px">អត្តសញ្ញាណប័ណ្ណ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($civilServants as $i => $emp)
                <tr>
                    <td>{{ $offset + $i + 1 }}</td>
                    <td>{{ trim(($emp->last_name_kh ?? '') . ' ' . ($emp->first_name_kh ?? '')) }}</td>
                    <td>{{ $emp->gender_id == 1 ? 'ប្រុស' : 'ស្រី' }}</td>
                    <td>{{ $emp->position->name_kh ?? $emp->position->name_short ?? $emp->position->abb ?? 'N/A' }}</td>
                    <td>{{ $emp->department_name ?? 'N/A' }}</td>
                    <td>{{ $emp->sub_department_name ?? 'N/A' }}</td>
                    <td>
                        @if($emp->has_id_card)
                            <span class="badge-yes">&#10003; មាន</span>
                        @else
                            <span class="badge-no">&#10007; គ្មាន</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        បោះពុម្ភនៅ {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
