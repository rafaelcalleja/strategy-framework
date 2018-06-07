<?php

class ArrayChildItemList extends ArrayObjectList
{
    public function render($template, $data = []) {
        $tpl = new Plantilla();

        foreach ($data as $key => $value) {
            $tpl->assign($key, $value);
        }

        $tpl->assign("items", $this);

        return $tpl->getHTML($template);
    }
}
