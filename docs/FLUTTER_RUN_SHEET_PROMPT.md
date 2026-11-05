# Flutter prompt: replace the SIR form with the Emergency Medical Response run sheet

Paste everything below into your Flutter coding assistant, inside the E-Response app project.

---

Replace the app's existing Situational Incident Report (SIR) form with the new Emergency Medical Response run sheet. Keep the existing human body diagram and its marking behaviour exactly as they are.

## Before you change anything

1. Find the current SIR screens, model, API service and history list, and read how they work.
2. Tell me which files you will change. Then make the change.

## Rules

- The backend is already updated. Do not ask for backend changes. Base URL: `https://mdrrmo-tago.online`.
- Send `Authorization: Bearer <sanctum_token>` and `Accept: application/json` on every request below.
- Keep the same endpoints: `POST /api/v1/situational-incident-reports` to create, `PUT /api/v1/situational-incident-reports/{id}` to update, and the existing history routes. Success returns HTTP 200 with the report in `data`. Validation errors return HTTP 422 with field errors.
- **Every new or updated run sheet must send `"form_version": 2`.** Without it, the server files it as an old-format report.
- Do not delete the old report display. Reports with `form_version` 1 (or missing) must still open read-only with their old fields. Only new reports use the new form.
- Do not hard-code teams or rosters. Load them from the options endpoint below, and cache the last good response so the form still opens with a weak signal.
- Keep the existing save and error behaviour. A responder in the field must never lose what they typed: if saving fails, keep the form filled in and show the reason.
- **The server assigns `incident_no`** (for example `INC-2026-0001`) when a new run sheet is saved. Never generate, reserve or send one, and never let anyone edit it. Read it from `data.incident_no` in the save reply and show it to the responder straight away. A form must still open with no signal, because no number is needed until it saves.
- The server treats all fields as optional. Do not add required fields that could block a responder from saving during an emergency.

## Load the choices

`GET /api/v1/situational-incident-reports/run-sheet-options`

`data` contains:

- `teams`: an object keyed by `ALPHA`, `BRAVO`, `CHARLIE`, `DELTA`. Each has `members` (names), `team_leader` and `recorder`.
- `officers`: names under "MDRRM OFFICER/STAFF".
- `incident_types`, `airway`, `skin`, `sex`, `consciousness`, `pupils`, `transport_vehicles`, `dispositions`, `actions_taken`, `equipment`: objects of `key: label`. Send the key and show the label.
- `victim_statuses`: a plain list of text (`Single`, `Married`, `Widowed`, `Separated`), not `key: label`.
- `injury_natures`: an object with keys `"1"` to `"15"`. Send these as integers.

## Form layout, top to bottom

Use a scrolling mobile form with these sections, in this order and with these labels.

**Header.** Shows "NAME OF SERVICE: MDRRMO/SERTSS TAGO" as fixed text.

| Label | Field | Input |
|---|---|---|
| Team | `response_team` | Single choice from `teams`. Selecting a team fills in its roster, team leader and recorder |
| Incident no. | `incident_no` | Read-only, never sent. Before the first save, show "Assigned when saved" |
| Date | `incident_date` | Date picker, sent as `YYYY-MM-DD` |

**Event times.** Time pickers, sent as 24-hour `HH:mm`, with a "Now" button on each.

`time_informed`, `time_arrival_at_scene`, `time_departure_from_scene`, `time_arrival_at_hospital`, `time_back_in_service`

**Type of incident.** `incident_types`: multi-select list of keys. `incident_type_others`: text.

**Airway and skin.**

- `airway`: single choice.
- `blood_type`: text, up to 10 characters.
- `skin`: multi-select list of keys. `skin_others`: text.

**Address of incident.** `address_of_incident`: multi-line text.

**Victim.**

| Label | Field | Input |
|---|---|---|
| Victim's name | `victim_name` | Text |
| Age | `victim_age` | Text, up to 20 characters, so "8 months" works |
| Sex | `victim_sex` | Single choice |
| Status | `victim_status` | Dropdown from `victim_statuses`. Send the text itself. If an existing report holds a value not in the list, keep it and show it as selected |
| Address | `victim_address` | Multi-line text |
| Conscious / Unconscious | `consciousness` | Single choice |
| B.P. | `blood_pressure` | Text such as `120/80`, up to 20 characters |
| Pupils | `pupils` | Single choice |
| L / R | `pupil_left`, `pupil_right` | Two checkboxes, sent as booleans |

**Patient disposition.**

- Treated/Transport by EMS, `transport_vehicle`: single choice.
- `patient_disposition`: single choice.
- Checkboxes, sent as booleans: `is_work_related`, `suspected_alcohol_use`, `suspected_drug_use`, `patient_refused_care`, `patient_refused_transport`.

**Nature of injury and body references.**

- `injury_natures`: multi-select list of integers. If 15 (Others) is selected, show `injury_nature_others`.
- Keep the existing body diagram screen, image and tap-to-mark behaviour unchanged. Keep sending `injury_diagram` (base64 PNG) and `injury_markers` as you do today.
- Each marker may now also carry an optional `nature` integer from 1 to 15. Add a nature picker when a marker is placed, and show its number on the marker. Example: `{"x": 0.42, "y": 0.31, "label": "Left arm", "nature": 13}`.

**Actions taken.** `actions_taken`: multi-select list of keys. `actions_taken_others`: text.

**SAMPLE history.** Multi-line text, up to 1000 characters each.

| Letter | Field | Label |
|---|---|---|
| S | `sample_signs_symptoms` | Signs and symptoms |
| A | `sample_allergies` | Allergies |
| M | `sample_medications` | Medications |
| P | `sample_past_history` | Past medical history |
| L | `sample_last_intake` | Last oral intake |
| E | `sample_events` | Events leading up |

**Equipment used.** `equipment_used`: multi-select list of keys. `equipment_others`: text.

**Responders.** `responders`: a list of **names, not ids**.

- Show the selected team's `members` as checkboxes, then `officers` under the heading "MDRRM OFFICER/STAFF".
- Add a way to type a name for anyone not listed, and send it in the same list.
- If no team is selected, show only the officers and the free-text option.

**Patient belongings logistics.** `patient_belongings`: multi-line text.

**Signatures.**

- `team_leader_name`: filled in from the team, editable.
- `recorder_name`: filled in from the team, editable.
- `received_by`: "Noted/received/medical facilities", text.

## Example request

```json
{
  "form_version": 2,
  "response_team": "BRAVO",
  "incident_date": "2026-11-06",
  "time_informed": "08:15",
  "time_arrival_at_scene": "08:27",
  "incident_types": ["vehicular"],
  "airway": "partly_obstructed",
  "blood_type": "O+",
  "skin": ["pale", "sweaty"],
  "address_of_incident": "National highway, Brgy. Poblacion, Tago",
  "victim_name": "Example Victim",
  "victim_age": "34",
  "victim_sex": "female",
  "consciousness": "conscious",
  "blood_pressure": "110/70",
  "pupils": "reactive",
  "pupil_left": true,
  "pupil_right": true,
  "transport_vehicle": "ambulance",
  "patient_disposition": "treated_released",
  "injury_natures": [1, 13],
  "injury_markers": [{ "x": 0.42, "y": 0.31, "label": "Left arm", "nature": 13 }],
  "actions_taken": ["bandaging"],
  "sample_allergies": "None known",
  "equipment_used": ["gauze_pad", "c_collar"],
  "responders": ["REY S. RELOVA", "ROMEO LOZADA"],
  "patient_belongings": "One phone, handed to family",
  "team_leader_name": "REY S. RELOVA",
  "recorder_name": "JEANN T. EMBOC"
}
```

## History and detail screens

- In the list, show `incident_no`, `victim_name`, `incident_date` and the incident types when `form_version` is 2. Keep the current display for older reports.
- Editing a version 2 report opens this new form with every field filled in. `PUT` only what the form holds, and always include `"form_version": 2`.

## Do not change

Login, voice calling, message reports, location sharing and the existing body diagram image.

## When you finish

Confirm you tested these, and show the results:

1. Creating a run sheet with every section filled in, then reopening it and seeing the same values, with the assigned incident number shown after saving.
2. Saving with most fields empty.
3. Opening an older report, which still displays with its old fields.
4. Changing the team, which updates the roster, team leader and recorder.
5. A failed save with no signal, which keeps everything the responder typed.
