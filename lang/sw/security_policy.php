<?php

return [
    'title'    => 'Sera ya Nywila na Usalama wa Akaunti',
    'subtitle' => 'Kanuni zinazotekelezwa na mfumo kwa kila akaunti, zimewekwa hapa kwenye ukurasa mmoja kwa ushahidi wa ukaguzi.',
    'footer'   => 'Ukurasa huu unaonyesha kanuni zinazotekelezwa katika msimbo wa uthibitishaji wa mfumo. Unasasishwa kwa mkono kila zinapobadilika.',

    'group' => [
        'password' => 'Kanuni za nywila',
        'login'    => 'Ulinzi wa kuingia',
        'account'  => 'Akaunti na ukaguzi',
    ],

    'item' => [
        'min_length' => [
            'title' => 'Urefu wa chini wa nywila',
            'desc'  => 'Nywila ya kila akaunti lazima iwe na herufi 8 au zaidi.',
        ],
        'confirmation' => [
            'title' => 'Uthibitisho wa nywila unahitajika',
            'desc'  => 'Nywila mpya lazima iandikwe mara mbili na zifanane, wakati wa kujisajili, kubadilisha, au kuweka upya nywila.',
        ],
        'hashing' => [
            'title' => 'Nywila zinasimbwa, hazihifadhiwi wazi',
            'desc'  => 'Nywila zinasimbwa kwa bcrypt kabla ya kuhifadhiwa; thamani halisi haihifadhiwi wala kurekodiwa popote.',
        ],
        'reset_delivery' => [
            'title' => 'Kuweka upya nywila hutumia kiungo cha mara moja, kilichosainiwa',
            'desc'  => 'Kuweka upya nywila kunawezekana tu kupitia kiungo chenye muda maalum kilichotumwa kwa barua pepe iliyothibitishwa ya mmiliki wa akaunti; kiungo hicho hakiwezi kutumika tena.',
        ],
        'login_throttle' => [
            'title' => 'Majaribio ya kuingia yanadhibitiwa',
            'desc'  => 'Baada ya majaribio 5 yasiyofanikiwa ya kuingia kwa akaunti na anwani ya mtandao ile ile, majaribio zaidi huzuiwa kwa muda unaoongezeka.',
        ],
        'registration_throttle' => [
            'title' => 'Usajili unadhibitiwa kwa kiwango',
            'desc'  => 'Usajili wa akaunti mpya umewekewa kikomo cha majaribio 3 kwa dakika kutoka anwani moja ya mtandao.',
        ],
        'reset_throttle' => [
            'title' => 'Maombi ya kuweka upya nywila yanadhibitiwa kwa kiwango',
            'desc'  => 'Maombi ya kiungo cha kuweka upya nywila yamewekewa kikomo cha maombi 6 kwa dakika.',
        ],
        'deactivation' => [
            'title' => 'Akaunti zilizozimwa hutolewa nje mara moja',
            'desc'  => 'Akaunti iliyozimwa hutolewa nje kwenye ombi lake linalofuata, na kila kipindi amilifu cha akaunti hiyo hubatilishwa mara moja.',
        ],
        'auditing' => [
            'title' => 'Kila tukio la usalama linarekodiwa',
            'desc'  => 'Kuingia, kutoka, majaribio yasiyofanikiwa, kufungiwa, na mabadiliko ya nywila yote yanaandikwa kwenye kumbukumbu ya ukaguzi isiyoweza kubadilishwa.',
        ],
    ],
];
