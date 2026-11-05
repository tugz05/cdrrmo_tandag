@php
    /**
     * Emergency Medical Response run sheet. The grid copies the source workbook's
     * cells: column widths, and row heights at about its 60% print scale.
     *
     * @var \App\Models\SituationalIncidentReport $report
     * @var bool $embedded
     */
    $embedded = $embedded ?? false;

    $columns = [9, 9, 12.57, 8.86, 13.29, 9, 15.43, 10.43, 22.86, 10.29, 18.71, 6.43, 8.43, 14.86];
    $rows = [
        15, 15, 15, 15, 15,
        21.95, 21.95, 21.95, 21.95, 21.95, 21.95, 21.95, 21.95, 21.95,
        15, 15, 15, 15, 15, 15, 8.1,
        15, 15, 15, 15, 15,
        21, 26.25, 27, 30.75, 15, 15, 15, 15, 15, 9.75, 25.5, 15, 15, 15.75, 21, 15, 15, 15, 15, 15, 27,
        15, 15,
        21, 19.5, 19.5, 20.25, 21.75, 22.5, 22.5, 22.5, 22.5, 22.5, 22.5, 20.25, 20.25, 21.75, 21, 20.25, 20.25,
        15, 20.1, 15.95, 15.95, 9, 24, 23.1,
    ];

    // Times are stored as the app sent them; anything that isn't a time prints as written.
    $time12 = function (?string $value): ?string {
        $value = trim((string) $value);

        if (! preg_match('/^\d{1,2}:\d{2}(:\d{2})?(\s?[ap]m)?$/i', $value)) {
            return $value;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('g:i A');
        } catch (\Exception) {
            return $value;
        }
    };

    $has = fn (?array $selected, $key): bool => in_array($key, $selected ?? [], false);

    $team = $report->response_team ? config("run_sheet.teams.{$report->response_team}") : null;
    $members = array_pad($team['members'] ?? [], 7, null);
    $officers = config('run_sheet.officers');
    $selectedResponders = $report->responders ?? [];
    $extraResponders = array_values(array_diff($selectedResponders, $team['members'] ?? [], $officers));

    $teamLeader = $report->team_leader_name ?: ($team['team_leader'] ?? '');
    $recorder = $report->recorder_name ?: ($team['recorder'] ?? '');

    $equipment = array_keys(config('run_sheet.equipment'));
    $equipmentLabels = config('run_sheet.equipment');

    $sample = [
        ['S', 'sample_signs_symptoms', 27, 29],
        ['A', 'sample_allergies', 29, 31],
        ['M', 'sample_medications', 31, 35],
        ['P', 'sample_past_history', 35, 39],
        ['L', 'sample_last_intake', 39, 43],
        ['E', 'sample_events', 43, 48],
    ];

    $diagram = $report->injury_diagram_url ?? asset('assets/images/human.jpg');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Run sheet {{ $report->incident_no ?: '#'.$report->id }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Carlito:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 8.5mm 2mm 0;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        html,
        body {
            margin: 0;
            background: #fff;
        }

        body {
            font-family: Carlito, Calibri, Arial, sans-serif;
            font-size: 6.9pt;
            line-height: 1.15;
            color: #000;
        }

        .sheet {
            display: grid;
            grid-template-columns: @foreach ($columns as $width) {{ $width }}fr @endforeach;
            grid-template-rows: @foreach ($rows as $height) {{ round($height * 0.59, 2) }}pt @endforeach;
            width: 206mm;
            margin: 0 auto;
            border-top: 0.6pt solid #000;
            border-left: 0.6pt solid #000;
        }

        body.embedded .sheet {
            margin-top: 8.5mm;
        }

        .c {
            position: relative;
            display: flex;
            align-items: center;
            min-width: 0;
            min-height: 0;
            padding: 0 1.5pt;
            overflow: hidden;
            border-right: 0.6pt solid #000;
            border-bottom: 0.6pt solid #000;
        }

        .bold { font-weight: 700; }
        .center { justify-content: center; text-align: center; }
        .top { align-items: flex-start; }
        .head { justify-content: center; background: #000; font-weight: 700; color: #fff; text-align: center; }
        .value { font-size: 7.4pt; color: #0b1a45; }
        .type { font-size: 8.3pt; font-weight: 700; justify-content: center; text-align: center; }
        .type-plain { font-size: 8.3pt; justify-content: center; text-align: center; }
        .label-small { font-size: 5pt; font-weight: 700; }
        .no-border { border-right: 0; border-bottom: 0; }
        .rule-right { border-right: 0.6pt solid #000; }

        .box {
            position: absolute;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 10pt;
            height: 7.6pt;
            border: 1.1pt solid #000;
            font-size: 7.5pt;
            font-weight: 700;
            line-height: 1;
            background: #fff;
        }

        .box.green { border-color: #2e7d32; border-width: 0.7pt; }

        .box-inline {
            position: static;
            flex-shrink: 0;
        }

        .logo-left { position: absolute; left: 7%; top: 1pt; height: 41pt; }
        .logo-right { position: absolute; right: 4.4%; top: 3pt; height: 39pt; }

        .title {
            grid-column: 1 / 15;
            grid-row: 1 / 6;
            flex-direction: column;
            justify-content: center;
            font-size: 6.9pt;
            line-height: 1.32;
            text-align: center;
        }

        .row-text { position: absolute; white-space: nowrap; }

        .figure-title { position: absolute; top: 5.5pt; left: 0; right: 0; font-size: 7.8pt; font-weight: 700; text-align: center; }

        .figure {
            position: absolute;
            top: 19pt;
            left: 8%;
            width: 84%;
            height: calc(100% - 37pt);
            object-fit: contain;
        }

        .figure-mark { position: absolute; bottom: 8pt; font-size: 7pt; font-weight: 700; }

        .markers {
            position: absolute;
            right: 2pt;
            bottom: 1pt;
            left: 2pt;
            font-size: 5pt;
            text-align: center;
        }

        .sample-letter {
            flex-shrink: 0;
            width: 24pt;
            font-size: 25pt;
            font-weight: 700;
            line-height: 1;
        }

        .fit {
            flex: 1;
            min-width: 0;
            max-height: 100%;
            overflow: hidden;
            font-size: 7pt;
            color: #0b1a45;
            overflow-wrap: anywhere;
            white-space: pre-line;
        }

        .equipment { font-size: 9.2pt; font-weight: 700; }
        .responder { font-size: 9.2pt; font-weight: 700; }
        .team-name { justify-content: center; font-size: 9.2pt; font-weight: 700; }
        .officer-head { background: #a6a6a6; font-size: 9.2pt; font-weight: 700; color: #fff; }
        .nature-selected {
            margin-left: -1.5pt;
            padding: 0 3pt;
            border: 0.9pt solid #000;
            border-radius: 999px;
            background: #ffe45c;
            line-height: 1;
        }
        .list-row { display: flex; align-items: center; justify-content: space-between; width: 100%; height: 9.3pt; white-space: nowrap; }
        .signature-name { font-size: 10.6pt; font-weight: 700; justify-content: center; align-items: flex-end; border: 0; }
        .signature-label { font-size: 7.3pt; font-weight: 700; justify-content: center; border: 0; border-top: 0.6pt solid #000; align-items: flex-start; padding-top: 1pt; }

        .print-bar { margin: 8px auto; width: 206mm; font: 13px Arial, sans-serif; }

        @media print {
            .print-bar { display: none; }
        }
    </style>
</head>
<body class="{{ $embedded ? 'embedded' : '' }}">
@unless ($embedded)
    <div class="print-bar"><a href="#" onclick="window.print(); return false;">Print again</a></div>
@endunless

<main class="sheet">
    {{-- Header --}}
    <div class="c title">
        <img class="logo-left" src="{{ asset('assets/images/tago_logo.png') }}" alt="Official seal of the Municipality of Tago">
        <div class="bold">MUNICIPAL DISASTER RISK REDUCTION AND MANAGEMENT OFFICE</div>
        <div>SEARCH AND EMERGENCY RESPONSE TEAM OF SURIGAO DEL SUR</div>
        <div>TAGO, SURIGAO DEL SUR</div>
        <div class="bold">EMERGENCY MEDICAL RESPONSE</div>
        <img class="logo-right" src="{{ asset('assets/images/mdrrmo_logo.png') }}" alt="MDRRMC Tago logo">
    </div>

    <div class="c bold" style="grid-column: 1 / 8; grid-row: 6; font-size: 7.8pt;">NAME OF SERVICE: MDRRMO/<span style="color:#e00000">SERTSS TAGO</span></div>
    <div class="c bold" style="grid-column: 8 / 11; grid-row: 6; font-size: 7.8pt;">INCIDENT NO:&nbsp;<span class="value">{{ $report->incident_no }}</span></div>
    <div class="c bold" style="grid-column: 11 / 15; grid-row: 6; font-size: 7.8pt;">DATE:&nbsp;<span class="value">{{ $report->incident_date?->format('F j, Y') }}</span></div>

    {{-- Times, incident type, airway, skin --}}
    <div class="c head" style="grid-column: 1 / 4; grid-row: 7;">EVENT</div>
    <div class="c head" style="grid-column: 4 / 6; grid-row: 7;">TIME</div>
    <div class="c head" style="grid-column: 6 / 9; grid-row: 7;">TYPE OF INCIDENT</div>
    <div class="c" style="grid-column: 9; grid-row: 7;"></div>
    <div class="c head" style="grid-column: 10 / 12; grid-row: 7;">AIRWAY</div>
    <div class="c" style="grid-column: 12; grid-row: 7;"></div>
    <div class="c head" style="grid-column: 13 / 15; grid-row: 7;">SKIN</div>

    @foreach ([
        ['INFORMED', 'time_informed'],
        ['ARIVAL AT SCENE', 'time_arrival_at_scene'],
        ['DEPARTURE FORM SCENE', 'time_departure_from_scene'],
        ['ARIVAL AT HOSPITAL', 'time_arrival_at_hospital'],
        ['BACK IN SERVICE', 'time_back_in_service'],
    ] as $i => [$label, $field])
        <div class="c bold" style="grid-column: 1 / 4; grid-row: {{ 8 + $i }};">{{ $label }}</div>
        <div class="c center value" style="grid-column: 4 / 6; grid-row: {{ 8 + $i }};">{{ $time12($report->{$field}) }}</div>
    @endforeach

    @foreach (['vehicular', 'fire', 'maritime', 'immediate_transport'] as $i => $key)
        <div class="c {{ $key === 'immediate_transport' ? 'type-plain' : 'type' }}" style="grid-column: 6 / 9; grid-row: {{ 8 + $i }};">{{ config("run_sheet.incident_types.{$key}") }}</div>
        <div class="c center bold value" style="grid-column: 9; grid-row: {{ 8 + $i }};">{{ $has($report->incident_types, $key) ? '✓' : '' }}</div>
    @endforeach
    <div class="c top label-small" style="grid-column: 6 / 9; grid-row: 12;">OTHERS:&nbsp;<span class="value">{{ $report->incident_type_others }}</span></div>
    <div class="c" style="grid-column: 9; grid-row: 12;"></div>

    @foreach (['normal', 'partly_obstructed', 'obstructed'] as $i => $key)
        <div class="c" style="grid-column: 10 / 12; grid-row: {{ 8 + $i }};">
            {{ config("run_sheet.airway.{$key}") }}
            <span class="box" style="right: 22%; top: {{ $i === 0 ? '-1.5pt' : '0.5pt' }};">{{ $report->airway === $key ? '✓' : '' }}</span>
        </div>
    @endforeach
    <div class="c head" style="grid-column: 10 / 12; grid-row: 11;">BLOOD TYPE</div>
    <div class="c center value" style="grid-column: 10 / 12; grid-row: 12;">{{ $report->blood_type }}</div>
    @foreach (range(8, 12) as $row)
        <div class="c" style="grid-column: 12; grid-row: {{ $row }};"></div>
    @endforeach

    @foreach (['normal', 'pale', 'sweaty', 'cyanotic'] as $i => $key)
        <div class="c" style="grid-column: 13 / 15; grid-row: {{ 8 + $i }};">
            {{ config("run_sheet.skin.{$key}") }}
            <span class="box" style="right: 11%; top: {{ $i === 0 ? '-1.5pt' : '1pt' }};">{{ $has($report->skin, $key) ? '✓' : '' }}</span>
        </div>
    @endforeach
    <div class="c top label-small" style="grid-column: 13 / 15; grid-row: 12;">OTHERS:&nbsp;<span class="value">{{ $report->skin_others }}</span></div>

    <div class="c" style="grid-column: 1 / 15; grid-row: 13;"></div>

    <div class="c top bold" style="grid-column: 1 / 4; grid-row: 14 / 16; padding-top: 2pt;">ADDRESS OF INCIDENT:</div>
    <div class="c value" style="grid-column: 4 / 15; grid-row: 14 / 16;">{{ $report->address_of_incident }}</div>

    {{-- Victim --}}
    <div class="c head" style="grid-column: 1 / 4; grid-row: 16 / 18;">VICTIM'S NAME</div>
    <div class="c head" style="grid-column: 4; grid-row: 16 / 18;">AGE</div>
    <div class="c head" style="grid-column: 5; grid-row: 16 / 18;">SEX</div>
    <div class="c head" style="grid-column: 6; grid-row: 16 / 18;">STATUS</div>
    <div class="c head" style="grid-column: 7 / 10; grid-row: 16 / 18;">ADDRESS</div>
    <div class="c head" style="grid-column: 10; grid-row: 16 / 18;">CONSCIOUS</div>
    <div class="c head" style="grid-column: 11; grid-row: 16 / 18;">UNCONSCIOUSS</div>
    <div class="c head" style="grid-column: 12 / 15; grid-row: 16 / 18;">B.P PUPILS</div>

    <div class="c value" style="grid-column: 1 / 4; grid-row: 18 / 22;">{{ $report->victim_name }}</div>
    <div class="c center value" style="grid-column: 4; grid-row: 18 / 22;">{{ $report->victim_age }}</div>
    <div class="c" style="grid-column: 5; grid-row: 18 / 22; padding: 0;">
        <span class="row-text" style="left: 2pt; top: 2pt;">Male</span>
        <span class="box" style="left: 56%; top: 1.5pt;">{{ $report->victim_sex === 'male' ? '✓' : '' }}</span>
        <span class="row-text" style="left: 2pt; top: 15.5pt;">Female</span>
        <span class="box" style="left: 56%; top: 15pt;">{{ $report->victim_sex === 'female' ? '✓' : '' }}</span>
    </div>
    <div class="c center value" style="grid-column: 6; grid-row: 18 / 22;">{{ $report->victim_status }}</div>
    <div class="c value" style="grid-column: 7 / 10; grid-row: 18 / 22;">{{ $report->victim_address }}</div>
    <div class="c center bold value" style="grid-column: 10; grid-row: 18 / 22;">{{ $report->consciousness === 'conscious' ? '✓' : '' }}</div>
    <div class="c center bold value" style="grid-column: 11; grid-row: 18 / 22;">{{ $report->consciousness === 'unconscious' ? '✓' : '' }}</div>
    <div class="c" style="grid-column: 12 / 15; grid-row: 18 / 22; padding: 0;">
        <span class="row-text" style="left: 8%; top: 1.2pt;">REACTIVE</span>
        <span class="box green" style="left: 41%; top: 0.5pt; width: 11pt; height: 6pt;">{{ $report->pupils === 'reactive' ? '✓' : '' }}</span>
        @if ($report->blood_pressure)
            <span class="row-text value" style="right: 2pt; top: 0.5pt;">{{ $report->blood_pressure }}</span>
        @endif
        <span class="row-text" style="left: 1pt; top: 9.5pt;">NON-REACTIVE</span>
        <span class="box green" style="left: 54%; top: 8.5pt; width: 11pt; height: 6pt;">{{ $report->pupils === 'non_reactive' ? '✓' : '' }}</span>
        <span class="row-text" style="left: 1pt; top: 18pt;">L</span>
        <span class="box green" style="left: 7%; top: 16.5pt; width: 11pt; height: 6pt;">{{ $report->pupil_left ? '✓' : '' }}</span>
        <span class="row-text" style="left: 40%; top: 18pt;">R</span>
        <span class="box green" style="left: 56%; top: 16.5pt; width: 11pt; height: 6pt;">{{ $report->pupil_right ? '✓' : '' }}</span>
    </div>

    {{-- Disposition --}}
    <div class="c head" style="grid-column: 1 / 11; grid-row: 22;">PATIENT DISPOSITION</div>
    <div class="c head" style="grid-column: 11 / 15; grid-row: 22;">SIGNATURE</div>

    <div class="c" style="grid-column: 1 / 4; grid-row: 23 / 27; padding: 0;">
        <span class="row-text" style="left: 8%; top: 2pt;">Treated/ Transport by EMS</span>
        <span class="box green" style="left: 1.5%; top: 9pt; width: 12pt; height: 7pt; border-color:#000;">{{ $report->transport_vehicle === 'ambulance' ? '✓' : '' }}</span>
        <span class="row-text" style="left: 24%; top: 10pt;">MDRRMO AMBULANCE</span>
        <span class="box green" style="left: 1.5%; top: 18pt; width: 12pt; height: 7pt; border-color:#000;">{{ $report->transport_vehicle === 'rescue_truck' ? '✓' : '' }}</span>
        <span class="row-text" style="left: 24%; top: 18.5pt;">MDRRMO RESCUE TRUCK</span>
    </div>
    <div class="c" style="grid-column: 4 / 8; grid-row: 23 / 27; padding: 0;">
        @foreach (['treated_released', 'no_treatment_required', 'dead_at_scene'] as $i => $key)
            <span class="box green" style="left: 5%; top: {{ 2 + ($i * 9.2) }}pt; width: 12pt; height: 8pt;">{{ $report->patient_disposition === $key ? '✓' : '' }}</span>
            <span class="row-text" style="left: {{ [34, 37, 36][$i] }}%; top: {{ 3 + ($i * 9) }}pt;">{{ config("run_sheet.dispositions.{$key}") }}</span>
        @endforeach
    </div>
    <div class="c" style="grid-column: 8 / 11; grid-row: 23 / 27; padding: 0;">
        @foreach ([['is_work_related', 'WORK RELATED', 37], ['suspected_alcohol_use', 'SUSPECTED ALCOHOL USE', 34], ['suspected_drug_use', 'SUSPECTED DRUG USE', 36]] as $i => [$field, $label, $left])
            <span class="box green" style="left: 7%; top: {{ 2 + ($i * 9.2) }}pt; width: 12pt; height: 8pt;">{{ $report->{$field} ? '✓' : '' }}</span>
            <span class="row-text" style="left: {{ $left }}%; top: {{ 3 + ($i * 9) }}pt;">{{ $label }}</span>
        @endforeach
    </div>
    <div class="c" style="grid-column: 11 / 15; grid-row: 23 / 27; padding: 0;">
        <span class="box green" style="left: 2.5%; top: 7pt; width: 12pt; height: 7pt;">{{ $report->patient_refused_care ? '✓' : '' }}</span>
        <span class="row-text" style="left: 14%; top: 8.5pt;">Patient Refused Care:</span>
        <span class="row-text" style="left: 63%; right: 5%; top: 14pt; border-top: 0.6pt solid #000;"></span>
        <span class="box green" style="left: 2.5%; top: 16pt; width: 12pt; height: 7pt;">{{ $report->patient_refused_transport ? '✓' : '' }}</span>
        <span class="row-text" style="left: 14%; top: 17pt;">Patient Refused Transport:</span>
        <span class="row-text" style="left: 72%; right: 5%; top: 23pt; border-top: 0.6pt solid #000;"></span>
    </div>

    {{-- Nature of injury and actions --}}
    <div class="c top bold" style="grid-column: 1 / 4; grid-row: 27 / 41; flex-direction: column; padding: 2.5pt 0 0 2pt;">
        <div class="list-row">&nbsp;NATURE OF INJURY:</div>
        @foreach (config('run_sheet.injury_natures') as $number => $label)
            <div class="list-row" style="justify-content: flex-start;">
                <span @class(['nature-selected' => $has($report->injury_natures, $number)])>{{ $number }}. {{ $label }}@if ($number === 15):@endif</span>
                @if ($number === 15)
                    @if ($report->injury_nature_others)
                        <span class="value" style="margin-left: 2pt; overflow: hidden;">{{ $report->injury_nature_others }}</span>
                    @else
                        <span style="flex: 1; align-self: flex-end; margin: 0 2pt 2pt; border-bottom: 0.6pt solid #888;"></span>
                    @endif
                @endif
            </div>
        @endforeach
    </div>
    <div class="c top bold" style="grid-column: 1 / 4; grid-row: 41 / 48; flex-direction: column; padding: 1pt 1pt 0 2pt;">
        <div class="list-row">ACTIONS TAKEN:</div>
        @foreach (config('run_sheet.actions_taken') as $key => $label)
            <div class="list-row">
                {{ $label }}
                <span class="box box-inline">{{ $has($report->actions_taken, $key) ? '✓' : '' }}</span>
            </div>
        @endforeach
        <div class="list-row" style="justify-content: flex-start;">OTHERS:&nbsp;<span class="value" style="font-weight: 400; overflow: hidden;">{{ $report->actions_taken_others }}</span></div>
    </div>

    {{-- Body references: the same figure the app marks up --}}
    <div class="c" style="grid-column: 4 / 11; grid-row: 27 / 48;">
        <div class="figure-title">BODY REFERENCES</div>
        <img class="figure" src="{{ $diagram }}" alt="Body diagram, front and back">
        <span class="figure-mark" style="left: 30%;">FRONT</span>
        <span class="figure-mark" style="right: 30%;">BACK</span>
        @if (! empty($report->injury_markers))
            <div class="markers">
                @foreach ($report->injury_markers as $marker)
                    {{ $loop->iteration }}. {{ $marker['label'] ?? 'Unspecified region' }}@unless ($loop->last); @endunless
                @endforeach
            </div>
        @endif
    </div>

    {{-- SAMPLE history --}}
    @foreach ($sample as [$letter, $field, $start, $end])
        <div class="c" style="grid-column: 11 / 15; grid-row: {{ $start }} / {{ $end }}; padding-left: 3pt;">
            <span class="sample-letter">{{ $letter }}</span>
            <div class="fit" data-fit>{{ $report->{$field} }}</div>
        </div>
    @endforeach

    {{-- Equipment and responders --}}
    <div class="c head" style="grid-column: 1 / 6; grid-row: 48 / 50; justify-content: flex-start; font-size: 9pt; padding-top: 3pt; align-items: flex-end;">EQUIPMENT USED:</div>
    <div class="c head" style="grid-column: 6 / 11; grid-row: 48 / 50; justify-content: center; font-weight: 400; font-size: 9.6pt;">RESPONDERS</div>
    <div class="c top bold" style="grid-column: 11 / 15; grid-row: 48 / 69; flex-direction: column; padding-top: 1.5pt;">
        <div style="align-self: center;">PATIENT BELONGINGS LOGISTICS:</div>
        <div class="fit" data-fit style="width: 100%; font-weight: 400; margin-top: 3pt;">{{ $report->patient_belongings }}</div>
    </div>

    @foreach ($equipment as $i => $key)
        <div class="c equipment" style="grid-column: 1 / 5; grid-row: {{ 50 + $i }};">
            {{ $equipmentLabels[$key] }}
            <span class="box" style="right: 2pt; top: 0.5pt; width: 10pt;">{{ $has($report->equipment_used, $key) ? '✓' : '' }}</span>
        </div>
    @endforeach
    <div class="c" style="grid-column: 1 / 5; grid-row: 65;">
        <span class="box" style="right: 2pt; top: 0.5pt; width: 10pt;"></span>
    </div>
    <div class="c" style="grid-column: 1 / 5; grid-row: 66;"></div>
    <div class="c equipment" style="grid-column: 1 / 5; grid-row: 67 / 69;">Others:&nbsp;<span class="value" style="font-weight: 400;">{{ $report->equipment_others }}</span></div>

    {{-- Team roster --}}
    <div class="c team-name" style="grid-column: 5 / 8; grid-row: 50;">{{ $report->response_team }}</div>
    <div class="c" style="grid-column: 8 / 10; grid-row: 50;"></div>
    <div class="c" style="grid-column: 10 / 11; grid-row: 50;"></div>
    @foreach (range(0, 6) as $i)
        <div class="c responder" style="grid-column: 5 / 8; grid-row: {{ 51 + $i }};">
            {{ $members[$i] }}
            <span class="box" style="right: 4%; top: 0.8pt; width: 10pt;">{{ $members[$i] && in_array($members[$i], $selectedResponders, true) ? '✓' : '' }}</span>
        </div>
        <div class="c" style="grid-column: 8 / 10; grid-row: {{ 51 + $i }};"></div>
        <div class="c" style="grid-column: 10 / 11; grid-row: {{ 51 + $i }};"></div>
    @endforeach
    @foreach (range(0, 2) as $i)
        <div class="c responder" style="grid-column: 5 / 8; grid-row: {{ 58 + $i }};">{{ $extraResponders[$i] ?? '' }}</div>
        <div class="c" style="grid-column: 8 / 10; grid-row: {{ 58 + $i }};"></div>
        <div class="c" style="grid-column: 10 / 11; grid-row: {{ 58 + $i }};"></div>
    @endforeach

    <div class="c officer-head" style="grid-column: 5 / 11; grid-row: 61;">MDRRM OFFICER/STAFF</div>
    @foreach ([[0, 1], [2, 3], [4, 5], [6, null]] as $i => [$left, $right])
        <div class="c responder" style="grid-column: 5 / 8; grid-row: {{ 62 + $i }};">
            {{ $officers[$left] }}
            <span class="box" style="right: 9%; top: 0.5pt; width: 10pt;">{{ in_array($officers[$left], $selectedResponders, true) ? '✓' : '' }}</span>
        </div>
        <div class="c responder" style="grid-column: 8 / 10; grid-row: {{ 62 + $i }};">
            {{ $right !== null ? $officers[$right] : '' }}
            <span class="box" style="right: 5%; top: 0.5pt; width: 10pt;">{{ $right !== null && in_array($officers[$right], $selectedResponders, true) ? '✓' : '' }}</span>
        </div>
        <div class="c" style="grid-column: 10 / 11; grid-row: {{ 62 + $i }};"></div>
    @endforeach
    <div class="c" style="grid-column: 5 / 10; grid-row: 66;"></div>
    <div class="c" style="grid-column: 10 / 11; grid-row: 66; outline: 0.6pt solid #888; outline-offset: -1.2pt;"></div>
    <div class="c" style="grid-column: 5 / 10; grid-row: 67;"></div>
    <div class="c" style="grid-column: 10 / 11; grid-row: 67;"></div>
    <div class="c" style="grid-column: 5 / 10; grid-row: 68;"></div>
    <div class="c" style="grid-column: 10 / 11; grid-row: 68;"></div>

    {{-- Signatures --}}
    <div class="c no-border rule-right" style="grid-column: 1 / 15; grid-row: 69 / 72;"></div>
    <div class="c signature-name" style="grid-column: 1 / 6; grid-row: 72; margin: 0 1.5%;">{{ $teamLeader }}</div>
    <div class="c signature-name" style="grid-column: 6 / 10; grid-row: 72; margin: 0 2%;">{{ $recorder }}</div>
    <div class="c signature-name" style="grid-column: 10 / 15; grid-row: 71 / 73; margin: 0 2.5% 5pt; border-bottom: 0.6pt solid #000;">{{ $report->received_by }}</div>
    <div class="c signature-label" style="grid-column: 1 / 6; grid-row: 73; margin: 0 1.5%;">TEAM LEADER'S PRINTED NAMES AND SIGNATURE</div>
    <div class="c signature-label" style="grid-column: 6 / 10; grid-row: 73; margin: 0 2%;">RECORDER'S PRINTED NAME &amp; SIGNATURE</div>
    <div class="c signature-label" style="grid-column: 10 / 15; grid-row: 73; margin: 0 2.5%; border-top: 0;">NOTED/RECEIVED/ MEDICAL FACILITIES</div>
</main>

<script>
    // A long entry shrinks to fit its box, so nothing on a medical record is cut off.
    document.querySelectorAll('[data-fit]').forEach((element) => {
        let size = 7;
        while (element.scrollHeight > element.clientHeight && size > 4) {
            size -= 0.25;
            element.style.fontSize = `${size}pt`;
        }
    });
    @unless ($embedded)
        window.addEventListener('load', () => window.print());
    @endunless
</script>
</body>
</html>
