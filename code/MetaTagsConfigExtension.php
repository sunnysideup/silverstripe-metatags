<?php

class MetaTagsConfigExtension extends DataExtension
{

    private static $db = array(
        'MetaGenerator' => 'boolean',
        'MetaMisc' => 'HTMLText',
    );

    public function updateCMSFields(FieldList $fields)
    {
        $generator = new CheckboxField('MetaGenerator', _t('MetaTags.GENERATOR.', 'Show the Generator Meta Tag'));
        $generator->setRightTitle(_t(
            'MetaTags.GENERATOR_HELP',
            'By default the site includes a metatag to identify the type of application being used for the site. You choose to sho/hide that tag'
        ))->addExtraClass('help');

        $misc = new TextareaField('MetaMisc', _t('MetaTags.METAMISC', 'Miscellaneous Meta Tags'));
        $misc->setRightTitle(_t(
            'MetaTags.METAMISC_HELP',
            'Here you can define any Miscellaneous Meta tags that are to be displayed on every page of the site'
        ))->addExtraClass('help');

        $fields->addFieldsToTab(
            'Root.MetaTags',
            array(
                $generator,
                $misc,
            )
        );

        return $fields;
    }
}
