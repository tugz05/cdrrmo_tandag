<?php

/**
 * The Emergency Medical Response run sheet (form version 2).
 * Labels keep the spelling of the printed form, so the print matches the paper copy.
 */
return [
    'teams' => [
        'ALPHA' => [
            'members' => ['JOSHUA CURADA', 'JULITO GONZALES', 'JEFFREY BESINGA', 'NIEL MONTERO', 'ALEXA ABAD', 'BUTCH JANVY SECARRO', 'EGBERT ANINO'],
            'team_leader' => 'JOSHUA CURADA',
            'recorder' => 'ALEXA ABAD',
        ],
        'BRAVO' => [
            'members' => ['REY S. RELOVA', 'GARNETH AWA', 'JAMES NOEL SALAS', 'JUDYMAR BALDOVINO', 'JEANN EMBOC', 'DWIGHT TUTOR', 'JADE CASIO'],
            'team_leader' => 'REY S. RELOVA',
            'recorder' => 'JEANN T. EMBOC',
        ],
        'CHARLIE' => [
            'members' => ['EDISON JAMERO', 'JOSEPH BALILI', 'RAYMOND BALILI', 'ERNIE CABRERA', 'ROMMEL BAUTISTA', 'JESON PAEL', 'ENRIQUE FALCON'],
            'team_leader' => 'EDISON JAMERO',
            'recorder' => 'JOSEPH BALILI',
        ],
        'DELTA' => [
            'members' => ['BERNARDO GOLORAN', 'EVE AMOR CUARTERON', 'ROMEO ANDREW DUPING', 'ALWIN GALES', 'ALREY CAGA-ANAN', 'RUBEN BAUTISTA JR.'],
            'team_leader' => 'BERNARDO GOLORAN',
            'recorder' => 'EVE AMOR CUARTERON',
        ],
    ],

    'officers' => ['ROMEO LOZADA', 'ETHEL JANE IHAPON', 'ALEX SALMAZAN', 'ANGELICA SALAS', 'JERRY GUAZON', 'NELSAN LUNA', 'VEA ELLA ESPINOZA'],

    'incident_types' => [
        'vehicular' => 'VEHICULAR ACCIDENT (V.A)',
        'fire' => 'FIRE ACCIDENT (F.A)',
        'maritime' => 'MARITIME ACCIDENT (M.A)',
        'immediate_transport' => 'IMMEDIATE TRANSPORT (I.T)',
    ],

    'airway' => [
        'normal' => 'NORMAL',
        'partly_obstructed' => 'PARTLY OBSTRACT',
        'obstructed' => 'OBSTRACT',
    ],

    'skin' => [
        'normal' => 'NORMAL',
        'pale' => 'PALE',
        'sweaty' => 'SWEATY',
        'cyanotic' => 'CYANOTIC',
    ],

    'sex' => ['male' => 'Male', 'female' => 'Female'],
    'consciousness' => ['conscious' => 'CONSCIOUS', 'unconscious' => 'UNCONSCIOUSS'],
    'pupils' => ['reactive' => 'REACTIVE', 'non_reactive' => 'NON-REACTIVE'],

    'transport_vehicles' => [
        'ambulance' => 'MDRRMO AMBULANCE',
        'rescue_truck' => 'MDRRMO RESCUE TRUCK',
    ],

    'dispositions' => [
        'treated_released' => 'Treated / Released',
        'no_treatment_required' => 'No Treatment Required',
        'dead_at_scene' => 'Dead at Scene',
    ],

    'injury_natures' => [
        1 => 'ABRASION',
        2 => 'AVULSION',
        3 => 'DISLOCATION',
        4 => 'SWELLING',
        5 => 'AMPUTATION',
        6 => 'BURN',
        7 => 'PUNCTURE',
        8 => 'GUNSHOT WOUND',
        9 => 'STAB WOUNDS',
        10 => 'FRACTURE',
        11 => 'SPRAIN/ STRAIN',
        12 => 'TENDERNESS',
        13 => 'LACERATION',
        14 => 'INCISION',
        15 => 'OTHERS',
    ],

    'actions_taken' => [
        'wound_dressing' => 'WOUND DRESSING',
        'bandaging' => 'BANDAGING',
        'splinting' => 'SPLINTING',
        'cpr' => 'CPR',
        'oxygen_administration' => 'OXYGEN ADMINISTRATION',
    ],

    'equipment' => [
        'triangular_bandage' => 'Triangular Bandage',
        'povidone_iodine' => 'Povidion Iodine',
        'hydrogen_peroxide' => 'Hydrogen Peroxide',
        'gauze_pad' => 'Gauze Pad',
        'face_mask' => 'Face Mask',
        'plaster_strips' => 'Plaster Strips',
        'splint' => 'Splint',
        'wheel_chair' => 'Wheel Chair',
        'cotton_ball' => 'Cotton Ball',
        'rope' => 'Rope',
        'latex_gloves' => 'Latex Gloves',
        'spine_board' => 'Spine board',
        'scoop_stretcher' => 'Scoop Stretcher',
        'folding_stretcher' => 'Folding Stretcher',
        'c_collar' => 'C-Collar',
    ],
];
