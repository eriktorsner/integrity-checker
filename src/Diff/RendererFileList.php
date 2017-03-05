<?php
namespace integrityChecker\Diff;

class RendererFileList extends RendererAbstract
{
    /**
     * Render and return a unified diff.
     *
     * @return string The unified diff.
     */
    public function render()
    {
        $out= array();
        $opCodes = $this->diff->getGroupedOpcodes();
        foreach($opCodes as $group) {
            $lastItem = count($group)-1;
            $i1 = $group[0][1];
            $i2 = $group[$lastItem][2];
            $j1 = $group[0][3];
            $j2 = $group[$lastItem][4];

            if($i1 == 0 && $i2 == 0) {
                $i1 = -1;
                $i2 = -1;
            }

            foreach($group as $code) {
                list($tag, $i1, $i2, $j1, $j2) = $code;
                if ($tag == 'replace') {
                    $originalRows = $this->diff->GetA($i1, $i2);
                    $newRows = $this->diff->GetB($j1, $j2);

                }

                if ($tag == 'delete') {

                }

                if ($tag == 'insert') {

                }

                /*if($tag == 'replace' || $tag == 'delete') {
                    $diff .= '-'.implode(PHP_EOL."-", $this->diff->GetA($i1, $i2)).PHP_EOL;
                }

                if($tag == 'replace' || $tag == 'insert') {
                    $diff .= '+'.implode(PHP_EOL."+", $this->diff->GetB($j1, $j2)).PHP_EOL;
                }*/
            }
        }
        return $diff;
    }
}