<?php

class MetaTagsConfigExtension extends DataExtension {

    private static $db = array(
        'MetaGenerator' => 'boolean',
    );

    public function updateCMSFields(FieldList $fields) {

        $generator = new CheckboxField('MetaGenerator', _t('MetaTags.GENERATOR.', 'Show the Generator Meta Tag'));

        $fields->addFieldsToTab(
            'Root.MetaTags',
            array(
                $generator,
            )
        );

        return $fields;
    }

}