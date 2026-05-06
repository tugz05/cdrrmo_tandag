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

    $line = function (?string $v): string {
        return trim((string) ($v ?? ''));
    };

    $dtReceived = $report->date_time_received;
    $dtStr = $dtReceived ? $dtReceived->format('d/m/Y g:i A') : '';

    $ref = strtolower((string) ($report->refer_to_hospital ?? ''));
    $refYes = $ref === 'yes';
    $refNo = $ref === 'no';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Situational Incident Report</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 10mm 8mm 10mm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            line-height: 1.12;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .screen-pad {
            width: 100%;
            max-width: 190mm;
            margin: 0 auto;
            padding: 8px;
        }

        @media print {
            .screen-pad {
                padding: 0;
                max-width: none;
            }

            .no-print {
                display: none !important;
            }
        }

        .no-print {
            margin-bottom: 8px;
            font-size: 11px;
        }

        .sir-page {
            width: 100%;
        }

        .header {
            display: grid;
            grid-template-columns: 24mm 1fr 28mm;
            align-items: start;
            margin-bottom: 6px;
        }

        .logo-box {
            text-align: center;
        }

        .logo-box img {
            max-width: 22mm;
            max-height: 22mm;
            object-fit: contain;
        }

        .right-logo img {
            max-width: 28mm;
            max-height: 18mm;
            object-fit: contain;
        }

        .header-center {
            text-align: center;
            font-weight: bold;
            line-height: 1.15;
        }

        .gov-text {
            font-size: 9pt;
        }

        .office-text {
            margin-top: 10px;
            font-size: 8.8pt;
        }

        .report-title {
            font-size: 9pt;
            margin-top: 2px;
        }

        .form-row {
            display: flex;
            align-items: flex-end;
            min-height: 5.7mm;
            margin-bottom: 0.5mm;
        }

        .label {
            font-weight: bold;
            white-space: nowrap;
            padding-right: 3px;
        }

        .fill {
            flex: 1;
            border-bottom: 1.2px solid #000;
            min-height: 4mm;
            padding-left: 3px;
            word-break: break-word;
        }

        .details-title {
            font-weight: bold;
            margin-top: 1.5mm;
            margin-bottom: 1mm;
        }

        .hint {
            font-weight: normal;
        }

        .line {
            border-bottom: 1.2px solid #000;
            min-height: 5.6mm;
            padding: 0 2px;
            font-size: 8pt;
            line-height: 1.1;
            word-break: break-word;
        }

        .line.tight {
            min-height: 5.2mm;
        }

        .maroon {
            color: #7b1113;
            font-weight: bold;
        }

        .section-gap {
            height: 4mm;
        }

        .checkbox-section {
            margin-top: 4mm;
        }

        .cb-row {
            display: flex;
            align-items: center;
            min-height: 5.2mm;
            font-weight: bold;
            font-size: 8.5pt;
        }

        .box {
            width: 6mm;
            height: 5.2mm;
            border: 1.2px solid #000;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 3mm;
            font-size: 8pt;
            font-weight: bold;
            line-height: 1;
        }

        .exam-title {
            text-align: center;
            font-weight: bold;
            margin-top: 1mm;
            margin-bottom: 1mm;
        }

        .exam-grid {
            display: grid;
            grid-template-columns: 31mm 42mm 1fr;
            column-gap: 5mm;
            align-items: start;
            margin-top: 1mm;
        }

        .injury-list {
            padding-top: 7mm;
        }

        .body-img-wrap {
            text-align: center;
        }

        .body-img {
            max-width: 38mm;
            max-height: 50mm;
            object-fit: contain;
        }

        .exam-notes {
            padding-top: 11mm;
        }

        .action-title {
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
            margin-top: 4mm;
            margin-bottom: 1mm;
        }

        .hospital-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            column-gap: 14mm;
            margin-top: 4mm;
            align-items: start;
        }

        .refer {
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 3mm;
            min-height: 7mm;
        }

        .small-box {
            width: 8mm;
            height: 4.5mm;
            border-bottom: 1.2px solid #000;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .right-info .form-row {
            min-height: 5.5mm;
        }

        .responders-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            column-gap: 12mm;
            margin-top: 5mm;
        }

        .response-vehicle {
            margin-top: 4mm;
        }

        .footer-space {
            height: 2mm;
        }
    </style>
</head>

<body>
    <div class="screen-pad">
        <div class="no-print">
            <a href="#" onclick="window.print(); return false;">Print again</a>
        </div>

        <div class="sir-page">

            {{-- HEADER --}}
            <div class="header">
                <div class="logo-box">
                    <img src="{{ asset('assets/images/tandag_logo.png') }}" alt="">
                </div>

                <div class="header-center">
                    <div class="gov-text">
                        REPUBLIC OF THE PHILIPPINES<br>
                        PROVINCE OF SURIGAO DEL SUR<br>
                        TANDAG CITY
                    </div>

                    <div class="office-text">
                        City Disaster Risk Reduction and Management Office<br>
                        SEARCH AND EMERGENCY RESPONSE TEAM OF SURIGAO DEL SUR
                    </div>

                    <div class="report-title">
                        Situational Incident Report
                    </div>
                </div>

                <div class="logo-box right-logo">
                    <img src="{{ asset('assets/images/icon.png') }}" alt="">
                </div>
            </div>

            {{-- BASIC DETAILS --}}
            <div class="form-row">
                <div class="label">Incident Type:</div>
                <div class="fill">{{ $line($report->incident_type) }}</div>
            </div>

            <div class="form-row">
                <div class="label">Caller/Source of Information:</div>
                <div class="fill">{{ $line($report->caller_source_of_information) }}</div>
            </div>

            <div class="form-row">
                <div class="label">Receiver:</div>
                <div class="fill">{{ $line($report->receiver) }}</div>
            </div>

            <div class="form-row">
                <div class="label">Date &amp; Time Received:</div>
                <div class="fill">{{ $dtStr }}</div>
            </div>

            <div class="form-row">
                <div class="label">Time of Response:</div>
                <div class="fill">{{ $line($report->time_of_response) }}</div>
            </div>

            <div class="form-row">
                <div class="label">Location:</div>
                <div class="fill">{{ $line($report->location) }}</div>
            </div>

            <div class="form-row">
                <div class="label">Landmark:</div>
                <div class="fill">{{ $line($report->landmark) }}</div>
            </div>

            {{-- INCIDENT DETAILS --}}
            <div class="details-title">
                Details of Incident:
                <span class="hint">(Name of Victim/s, Age, Sex, Address, Contact Number)</span>
            </div>

            @foreach ($fillLines($report->details_of_incident, 4) as $ln)
                <div class="line">{{ $ln }}</div>
            @endforeach

            <div class="section-gap"></div>

            {{-- VEHICLES --}}
            <div class="details-title">
                <span class="maroon">Vehicle/s Involved:</span>
                <span class="hint">(Vehicle Type &amp; Color, Plate Number, etc.)</span>
            </div>

            @foreach ($fillLines($report->vehicles_involved, 2) as $ln)
                <div class="line">{{ $ln }}</div>
            @endforeach

            {{-- RESPONSIVENESS --}}
            <div class="checkbox-section">
                <div class="maroon" style="margin-bottom: 1mm;">Assessing Responsiveness</div>

                <div class="cb-row">
                    <span class="box">{{ $report->is_alert_response ? '✓' : '' }}</span>
                    A - ALERT RESPONSE
                </div>

                <div class="cb-row">
                    <span class="box">{{ $report->is_verbal_response ? '✓' : '' }}</span>
                    V - VERBAL RESPONSE
                </div>

                <div class="cb-row">
                    <span class="box">{{ $report->is_pain_response ? '✓' : '' }}</span>
                    P - PAIN RESPONSE
                </div>

                <div class="cb-row">
                    <span class="box">{{ $report->is_unconscious ? '✓' : '' }}</span>
                    U - UNCONSCIOUS
                </div>
            </div>

            {{-- HEAD TO TOE --}}
            <div class="exam-title">PATIENT HEAD TO TOE EXAMINATION</div>

            <div class="exam-grid">
                <div class="injury-list">
                    <div class="cb-row">
                        <span class="box">{{ $report->has_deformity ? '✓' : '' }}</span>
                        D- DEFORMITY
                    </div>

                    <div class="cb-row">
                        <span class="box">{{ $report->has_contusion ? '✓' : '' }}</span>
                        C- CONTUSION
                    </div>

                    <div class="cb-row">
                        <span class="box">{{ $report->has_abrasion ? '✓' : '' }}</span>
                        A-ABRASION
                    </div>

                    <div class="cb-row">
                        <span class="box">{{ $report->has_puncture_penetration ? '✓' : '' }}</span>
                        P- PUNCTURE PENETRATION
                    </div>

                    <div class="cb-row">
                        <span class="box">{{ $report->has_tenderness ? '✓' : '' }}</span>
                        T- TENDERNESS
                    </div>

                    <div class="cb-row">
                        <span class="box">{{ $report->has_laceration ? '✓' : '' }}</span>
                        L- LACERATION
                    </div>

                    <div class="cb-row">
                        <span class="box">{{ $report->has_swelling ? '✓' : '' }}</span>
                        S- SWELLING
                    </div>
                </div>

                <div class="body-img-wrap">
                    <img class="body-img" src="{{ asset('assets/images/human.jpg') }}" alt="">
                </div>

                <div class="exam-notes">
                    @foreach ($fillLines($report->examination_notes, 6) as $nln)
                        <div class="line tight">{{ $nln }}</div>
                    @endforeach
                </div>
            </div>

            {{-- ACTION TAKEN --}}
            <div class="action-title">Action Taken</div>

            @foreach ($fillLines($report->action_taken, 3) as $aln)
                <div class="line">{{ $aln }}</div>
            @endforeach

            {{-- HOSPITAL --}}
            <div class="hospital-row">
                <div class="refer">
                    <span>Refer to Hospital?</span>
                    <span class="small-box">{{ $refYes ? '✓' : '' }}</span>
                    <span>Yes,</span>
                    <span class="small-box">{{ $refNo ? '✓' : '' }}</span>
                    <span>No</span>
                </div>

                <div class="right-info">
                    <div class="form-row">
                        <div class="label">Time Transported:</div>
                        <div class="fill">{{ $line($report->time_transported) }}</div>
                    </div>

                    <div class="form-row">
                        <div class="label">Name of Hospital:</div>
                        <div class="fill">{{ $line($report->name_of_hospital) }}</div>
                    </div>
                </div>
            </div>

            {{-- RESPONDERS --}}
            <div class="responders-grid">
                <div>
                    <div class="details-title">Name of Responders:</div>
                    @foreach ($fillLines($report->name_of_responders, 3) as $rln)
                        <div class="line tight">{{ $rln }}</div>
                    @endforeach
                </div>

                <div style="padding-top: 7mm;">
                    @foreach ($fillLines('', 3) as $blank)
                        <div class="line tight"></div>
                    @endforeach
                </div>
            </div>

            <div class="response-vehicle">
                <div class="form-row">
                    <div class="label">Name of Response Vehicle:</div>
                    <div class="fill">{{ $line($report->name_of_response_vehicle) }}</div>
                </div>
            </div>

            <div class="footer-space"></div>
        </div>
    </div>

    <script>
        (function () {
            function triggerPrint() {
                window.print();
            }

            if (document.readyState === 'complete') {
                setTimeout(triggerPrint, 350);
            } else {
                window.addEventListener('load', function () {
                    setTimeout(triggerPrint, 350);
                });
            }
        })();
    </script>
</body>
</html>
