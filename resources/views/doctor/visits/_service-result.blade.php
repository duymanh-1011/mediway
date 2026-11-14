@php
    $templateRows = $result->templateDisplayRows();
    $templateValues = array_column($templateRows, 'value');
    $resultRows = [
        ...$result->technicalDetailRows(),
        ...$result->parameterDisplayRows(),
        ...$templateRows,
    ];
    if (filled($result->result_summary) && !in_array($result->result_summary, $templateValues, true)) {
        array_unshift($resultRows, ['label' => 'Mô tả kết quả', 'value' => $result->result_summary]);
    }
    if (filled($result->result_conclusion) && !in_array($result->result_conclusion, $templateValues, true)) {
        $resultRows[] = ['label' => 'Kết luận dịch vụ', 'value' => $result->result_conclusion];
    }
@endphp

@if ($resultRows)
    <dl class="doctor-visit__result-values">
        @foreach ($resultRows as $row)
            <div>
                <dt>{{ $row['label'] }}</dt>
                @if (isset($row['measurements']))
                    <dd>
                        <div class="employee-table-wrap" tabindex="0" role="region" aria-label="{{ $row['label'] }}">
                            <table class="employee-table">
                                <thead><tr>
                                    <th scope="col">Chỉ số</th><th scope="col">Kết quả</th><th scope="col">Đơn vị</th>
                                    <th scope="col">Tham chiếu</th><th scope="col">Cờ kết quả</th>
                                </tr></thead>
                                <tbody>
                                    @foreach ($row['measurements'] as $measurement)
                                        <tr>
                                            <td>{{ $measurement['name'] ?? '—' }}</td><td>{{ $measurement['value'] ?? '—' }}</td>
                                            <td>{{ $measurement['unit'] ?? '—' }}</td><td>{{ $measurement['reference'] ?? '—' }}</td>
                                            <td>{{ $measurement['flag'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </dd>
                @else
                    <dd>{{ $row['value'] }}</dd>
                @endif
            </div>
        @endforeach
    </dl>
@endif
