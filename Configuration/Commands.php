<?php
return [
    'operator:installScheduledTasks' => [
        'class' => \Glowpointzero\SiteOperator\Command\InstallScheduledTasksCommand::class
    ],
    'operator:generateStaticResources' => [
        'class' => \Glowpointzero\SiteOperator\Command\GenerateStaticResourcesCommand::class
    ],
    'operator:symlink' => [
        'class' => \Glowpointzero\SiteOperator\Command\SymlinkCommand::class
    ],
    'operator:siteCheckup' => [
        'class' => \Glowpointzero\SiteOperator\Command\SiteCheckupCommand::class
    ]
];
