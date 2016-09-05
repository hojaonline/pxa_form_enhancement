<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "pxa_form_enhancement"
 *
 * Auto generated by Extension Builder 2016-06-13
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
	'title' => 'Pxa Form Enhancement',
	'description' => 'Allow to add recaptcha to TYPO3 form and save form post processor',
	'category' => 'fe',
	'author' => 'Andriy Oprysko',
	'author_email' => 'andriy@pixelant.se',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => '1',
	'createDirs' => '',
	'clearCacheOnLoad' => 1,
	'version' => '1.0.1',
	'constraints' => [
		'depends' => [
			'typo3' => '7.6.0-7.6.99',
		],
		'conflicts' => [
		],
		'suggests' => [
		],
	],
    'autoload' =>
        [
            'classmap' => [
                'Classes',
            ]
        ],
];