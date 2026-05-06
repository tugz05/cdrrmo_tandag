@php
    /** @var \App\Models\SituationalIncidentReport $report */

    $fillLines = function (?string $text, int $count): array {
        $lines = preg_split("/\r\n|\n|\r/", (string) ($text ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        $lines = array_values(array_map('trim', $lines));
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = $lines[$i] ?? '';
        }
        return $out;
    };

    $line = fn (?string $v) => trim((string) ($v ?? ''));

    $dtReceived = $report->date_time_received;
    $dtStr = $dtReceived ? $dtReceived->format('m/d/Y h:i A') : '';

    $ref = strtolower((string) ($report->refer_to_hospital ?? ''));
    $refYes = $ref === 'yes';
    $refNo = $ref === 'no';

    $details = $fillLines($report->details_of_incident, 4);
    $vehicles = $fillLines($report->vehicles_involved, 2);
    $examNotes = $fillLines($report->examination_notes, 6);
    $actions = $fillLines($report->action_taken, 3);

    $responders = $fillLines($report->name_of_responders, 6);
    $respondersLeft = array_slice($responders, 0, 3);
    $respondersRight = array_slice($responders, 3, 3);
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Situational Incident Report</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 210mm;
            height: 297mm;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            background: #fff;
        }

        .no-print {
            padding: 8px;
            font-size: 12px;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }

        .paper {
            position: relative;
            width: 210mm;
            height: 297mm;
            overflow: hidden;
            margin: 0 auto;
            background: #fff;
        }

        /* The blank form image itself */
        .template {
            position: absolute;
            top: 0;
            left: 0;
            width: 210mm;
            height: 297mm;
            object-fit: fill;
            z-index: 1;
        }

        .overlay {
            position: absolute;
            inset: 0;
            z-index: 2;
        }

        .txt {
            position: absolute;
            font-size: 8.2pt;
            line-height: 1.0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: clip;
        }

        .txt-small {
            position: absolute;
            font-size: 7.9pt;
            line-height: 1.0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: clip;
        }

        .tick {
            position: absolute;
            font-size: 12pt;
            line-height: 1;
            font-weight: bold;
        }

        .line-block {
            position: absolute;
            font-size: 8pt;
            line-height: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: clip;
        }

        .debug {
            display: none;
        }
    </style>
</head>
<body>

<div class="no-print">
    <a href="#" onclick="window.print(); return false;">Print again</a>
</div>

<div class="paper">
    {{-- IMPORTANT: use your blank form template here --}}
    <img class="template" src="{{ asset('assets/images/sir_template.png') }}" alt="SIR Template">

    <div class="overlay">

        {{-- TOP FIELDS --}}
        <div class="txt" style="top: 42.8mm; left: 41.5mm; width: 145mm;">
            {{ $line($report->incident_type) }}
        </div>

        <div class="txt" style="top: 49.0mm; left: 58.0mm; width: 129mm;">
            {{ $line($report->caller_source_of_information) }}
        </div>

        <div class="txt" style="top: 55.1mm; left: 28.5mm; width: 158mm;">
            {{ $line($report->receiver) }}
        </div>

        <div class="txt" style="top: 61.3mm; left: 47.0mm; width: 140mm;">
            {{ $dtStr }}
        </div>

        <div class="txt" style="top: 67.5mm; left: 43.5mm; width: 143mm;">
            {{ $line($report->time_of_response) }}
        </div>

        <div class="txt" style="top: 73.7mm; left: 26.5mm; width: 160mm;">
            {{ $line($report->location) }}
        </div>

        <div class="txt" style="top: 79.9mm; left: 27.5mm; width: 159mm;">
            {{ $line($report->landmark) }}
        </div>

        {{-- DETAILS OF INCIDENT --}}
        @foreach ($details as $i => $ln)
            <div class="line-block" style="top: {{ 89.0 + ($i * 6.05) }}mm; left: 8.2mm; width: 180mm;">
                {{ $ln }}
            </div>
        @endforeach

        {{-- VEHICLES --}}
        @foreach ($vehicles as $i => $ln)
            <div class="line-block" style="top: {{ 116.1 + ($i * 6.15) }}mm; left: 8.2mm; width: 180mm;">
                {{ $ln }}
            </div>
        @endforeach

        {{-- RESPONSIVENESS CHECKS --}}
        @if($report->is_alert_response)
            <div class="tick" style="top: 133.6mm; left: 7.9mm;">✓</div>
        @endif
        @if($report->is_verbal_response)
            <div class="tick" style="top: 139.9mm; left: 7.9mm;">✓</div>
        @endif
        @if($report->is_pain_response)
            <div class="tick" style="top: 146.1mm; left: 7.9mm;">✓</div>
        @endif
        @if($report->is_unconscious)
            <div class="tick" style="top: 152.3mm; left: 7.9mm;">✓</div>
        @endif

        {{-- BODY EXAM LEFT CHECKS --}}
        @if($report->has_deformity)
            <div class="tick" style="top: 165.4mm; left: 7.9mm;">✓</div>
        @endif
        @if($report->has_contusion)
            <div class="tick" style="top: 171.1mm; left: 7.9mm;">✓</div>
        @endif
        @if($report->has_abrasion)
            <div class="tick" style="top: 176.8mm; left: 7.9mm;">✓</div>
        @endif
        @if($report->has_puncture_penetration)
            <div class="tick" style="top: 182.4mm; left: 7.9mm;">✓</div>
        @endif
        @if($report->has_tenderness)
            <div class="tick" style="top: 188.1mm; left: 7.9mm;">✓</div>
        @endif
        @if($report->has_laceration)
            <div class="tick" style="top: 193.8mm; left: 7.9mm;">✓</div>
        @endif
        @if($report->has_swelling)
            <div class="tick" style="top: 199.4mm; left: 7.9mm;">✓</div>
        @endif

        {{-- EXAM NOTES --}}
        @foreach ($examNotes as $i => $ln)
            <div class="line-block" style="top: {{ 171.0 + ($i * 6.1) }}mm; left: 111.0mm; width: 71mm;">
                {{ $ln }}
            </div>
        @endforeach

        {{-- ACTION TAKEN --}}
        @foreach ($actions as $i => $ln)
            <div class="line-block" style="top: {{ 219.3 + ($i * 6.1) }}mm; left: 8.2mm; width: 180mm;">
                {{ $ln }}
            </div>
        @endforeach

        {{-- REFER TO HOSPITAL --}}
        @if($refYes)
            <div class="tick" style="top: 239.2mm; left: 48.7mm;">✓</div>
        @endif

        @if($refNo)
            <div class="tick" style="top: 239.2mm; left: 64.6mm;">✓</div>
        @endif

        {{-- TIME TRANSPORTED / HOSPITAL --}}
        <div class="txt" style="top: 239.8mm; left: 136.8mm; width: 42mm;">
            {{ $line($report->time_transported) }}
        </div>

        <div class="txt" style="top: 246.9mm; left: 137.0mm; width: 42mm;">
            {{ $line($report->name_of_hospital) }}
        </div>

        {{-- RESPONDERS LEFT --}}
        @foreach ($respondersLeft as $i => $ln)
            <div class="line-block" style="top: {{ 262.8 + ($i * 6.2) }}mm; left: 8.2mm; width: 70mm;">
                {{ $ln }}
            </div>
        @endforeach

        {{-- RESPONDERS RIGHT --}}
        @foreach ($respondersRight as $i => $ln)
            <div class="line-block" style="top: {{ 262.8 + ($i * 6.2) }}mm; left: 101.5mm; width: 70mm;">
                {{ $ln }}
            </div>
        @endforeach

        {{-- RESPONSE VEHICLE --}}
        <div class="txt" style="top: 283.6mm; left: 57.8mm; width: 76mm;">
            {{ $line($report->name_of_response_vehicle) }}
        </div>

    </div>
</div>

<script>
    (function () {
        function triggerPrint() {
            window.print();
        }

        if (document.readyState === 'complete') {
            setTimeout(triggerPrint, 500);
        } else {
            window.addEventListener('load', function () {
                setTimeout(triggerPrint, 500);
            });
        }
    })();
</script>

</body>
</html>
