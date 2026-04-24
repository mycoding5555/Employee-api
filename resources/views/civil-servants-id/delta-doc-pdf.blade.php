<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>ឯកសារ - {{ trim(($cs->last_name_kh ?? '') . ' ' . ($cs->first_name_kh ?? '')) }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'khmerossiemreap', 'khmeros', sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1a56db;
        }
        .header h1 {
            font-family: 'khmerosmuollight', 'khmeros', sans-serif;
            font-size: 15px;
            font-weight: bold;
            color: #1a56db;
            margin-bottom: 4px;
        }
        .header p { font-size: 11px; color: #555; }
        .info-block {
            margin-bottom: 14px;
            padding: 8px 10px;
            background: #f0f4ff;
            border-left: 4px solid #1a56db;
            border-radius: 2px;
        }
        .info-block strong { font-size: 12px; }
        .info-block span { color: #555; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
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
        tr:nth-child(even) { background-color: #f8f9fa; }
        .empty { text-align: center; color: #888; padding: 20px; font-style: italic; }
        .footer {
            margin-top: 16px;
            text-align: right;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>បញ្ជីឯកសារប្រភេទទី ១៦</h1>
        <p>{{ now()->format('d/m/Y') }}</p>
    </div>

    <div class="info-block">
        <strong>{{ trim(($cs->last_name_kh ?? '') . ' ' . ($cs->first_name_kh ?? '')) }}</strong>
        &nbsp;|&nbsp;
        <span>{{ $cs->position->name_kh ?? $cs->position->name_short ?? $cs->position->abb ?? '' }}</span>
        &nbsp;|&nbsp;
        <span>{{ $cs->department->name_kh ?? '' }}</span>
    </div>

    @if($deltas->isNotEmpty())
    <table>
        <thead>
            <tr>
                <th style="width:30px">ល.រ</th>
                <th>លេខឯកសារ</th>
                <th>ឈ្មោះឯកសារ</th>
                <th>លេខយោង</th>
                <th>កាលបរិច្ឆេទ</th>
                <th>ចំណងជើងចម្បង</th>
                <th>ចំណងជើងរង</th>
                <th>កំណត់ចំណាំ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($deltas as $i => $delta)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $delta->code ?? '' }}</td>
                <td>{{ $delta->document_name ?? '' }}</td>
                <td>{{ $delta->ref_number ?? '' }}</td>
                <td>{{ $delta->ref_date ? \Carbon\Carbon::parse($delta->ref_date)->format('d/m/Y') : '' }}</td>
                <td>{{ $delta->main_index ?? '' }}</td>
                <td>{{ $delta->index ?? '' }}</td>
                <td>{{ $delta->description ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="empty">គ្មានឯកសារប្រភេទទី ១៦ សម្រាប់មន្រ្តីរូបនេះ។</p>
    @endif

    <div class="footer">បោះពុម្ពនៅ {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
