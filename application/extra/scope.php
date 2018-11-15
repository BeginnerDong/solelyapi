<?php
/**
 * Created by 董博明.
 * Author: 董博明
 * Date: 2017/12/29
 * Time: 10:54
 */
return [
    


    'one'=>[
        0=>[
            30=>[],
            60=>[
                0=>'canChild',
                1=>'canChild',
                2=>'canChild',
            ],
            90=>[
                0=>'All',
                1=>'All',
                2=>'All',
            ],
        ],
        1=>[
            30=>[],
            60=>[
                0=>'canChild',
                1=>'canChild',
                2=>'canChild',
            ],
            90=>[
                0=>'All',
                1=>'All',
                2=>'All',
            ],
        ],
        2=>[
            30=>[],
            60=>[
                0=>'canChild',
                1=>'canChild',
                2=>'canChild',
            ],
            90=>[
                0=>'All',
                1=>'All',
                2=>'All',
            ],
        ],
	],

	'two'=>[
        0=>[
            0=>[],
            30=>[
            	0=>'isMe'
            ],
            60=>[
                0=>'canChild',
                1=>'canChild',
                2=>'canChild',
            ],
            90=>[
                0=>'All',
                1=>'All',
                2=>'All',
            ],
        ],
        1=>[
            0=>[],
            30=>[
            	1=>'isMe'
            ],
            60=>[
                0=>'canChild',
                1=>'canChild',
                2=>'canChild',
            ],
            90=>[
                0=>'All',
                1=>'All',
                2=>'All',
            ],
        ],
        2=>[
            0=>[],
            30=>[
            	2=>'canChild'
            ],
            60=>[
                2=>'canChild',
                0=>'All',
                1=>'All',
            ],
            90=>[
                0=>'All',
                1=>'All',
                2=>'All',
            ],
        ],
    ],

    'three'=>[

        0=>[
            0=>[],
            30=>[
                0=>'canChild'
            ],
            60=>[
                0=>'canChild',
                1=>'canChild',
                2=>'canChild',
            ],
            90=>[
                0=>'All',
                1=>'All',
                2=>'All',
            ],
        ],

        1=>[
            0=>[],
            30=>[
                1=>'canChild'
            ],
            60=>[
                0=>'canChild',
                1=>'canChild',
                2=>'canChild',
            ],
            90=>[
                0=>'All',
                1=>'All',
                2=>'All',
            ],
        ],

        2=>[
            0=>[],
            30=>[
                2=>'canChild'
            ],
            60=>[
                2=>'canChild',
                0=>'canChild',
                1=>'canChild',
            ],
            90=>[
                0=>'All',
                1=>'All',
                2=>'All',
            ],
        ],
        
    ],
    'six'=>[

        0=>[
            0=>[],
            30=>[
            	0=>'All'
            ],
            60=>[
                0=>'All',
                1=>'All',
                2=>'All',
            ],
            90=>[
                0=>'All',
                1=>'All',
                2=>'All',
            ],
        ],

        1=>[
            0=>[],
            30=>[
            	1=>'All'
            ],
            60=>[
                0=>'All',
                1=>'All',
                2=>'All',
            ],
            90=>[
                0=>'All',
                1=>'All',
                2=>'All',
            ],
        ],

        2=>[
            0=>[],
            30=>[
            	2=>'All'
            ],
            60=>[
                2=>'All',
                0=>'All',
                1=>'All',
            ],
            90=>[
                0=>'All',
                1=>'All',
                2=>'All',
            ],
        ],
        
    ]


















];