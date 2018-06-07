<?php

class ArrayObjectTemplateEmail extends ArrayObjectList
{
    public function toComaList()
    {
        return new ArrayIntList(array_map(function($item) {
            return $item->getUID();
        }, $this->getArrayCopy()));
    }

    public function getViewData(elemento $contact = null, $config = 0, $extraData = null, $options = true)
    {
        $db = db::singleton();
        $table = TABLE_PLANTILLAEMAIL;
        $SQL = "SELECT uid_plantillaemail
        FROM $table
        WHERE uid_plantillaemail IN ({$this->toComaList()}) ";

        $templates = $db->query($SQL, "*", 0, "plantillaemail");

        $contactTemplates = $contact->getArrayPlantillas();
        $isPrincipal = $contact->esPrincipal();
        $optionalTemplates = new ArrayIntList(plantillaemail::$templatesToAvoid);
        $optionalTemplates = $optionalTemplates->toObjectList("plantillaemail");

        $viewData = [];
        foreach ($templates as $template) {
            $checked = $contactTemplates->contains($template);
            $locked = false;

            if ($isPrincipal && !$optionalTemplates->contains($template)) {
                $checked = true;
                $locked = true;
            }

            $viewData[$template->getUID()] = [
                'uid' => $template->getUID(),
                'name' => "plantillaemail_name_{$template->getName()}",
                'description' => "plantillaemail_{$template->getName()}",
                'checked' => $checked,
                'locked' => $locked,
            ];
        }
        return $viewData;
    }
}