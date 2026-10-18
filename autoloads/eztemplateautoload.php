<?php

$eZTemplateOperatorArray = array(
    array(
        "script" => "extension/explayouts/classes/explblockoperator.php",
        "class" => "ExplBlockOperator",
        "operator_names" => array( 'expl_parse', 'expl_strip', 'expl_has', 'expl_first' )
    ),
    array(
        "script" => "extension/explayouts/classes/explayoutssafevalue.php",
        "class" => "expLayoutsSafeValue",
        "operator_names" => array( 'explayouts_safe_url', 'explayouts_safe_embed_url', 'explayouts_safe_color', 'explayouts_safe_tag', 'explayouts_safe_template_name' )
    )
);

$eZTemplateFunctionArray = array(
    array(
        "script" => "extension/explayouts/classes/explblockfunction.php",
        "class" => "ExplBlockFunction",
        "function_names" => array( 'explblock' )
    )
);
