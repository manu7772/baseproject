<?php

namespace ensemble01\services;

use Ensepar\Html2pdf\_class\HTML2PDF_myPdf as HtPmyPDF;

class HTML2PDF_myPdf extends HtPmyPDF {

    /**
     * create a automatic Index on a page
     *
     * @param html2pdf $obj           parent object
     * @param string   $titre         Title of the Index Page
     * @param integer  $sizeTitle     Font size for hthe Title
     * @param integer  $sizeBookmark  Font size for the bookmarks
     * @param boolean  $bookmarkTitle Bookmark the Title
     * @param boolean  $displayPage   Display the page number for each bookmark
     * @param integer  $page draw the automatic Index on a specific Page. if null => add a page at the end
     * @param string   $fontName      FontName to use
     * @access public
     */
    public function createIndex(
        &$obj,
        $titre = 'Index',
        $sizeTitle = 20,
        $sizeBookmark = 15,
        $bookmarkTitle = true,
        $displayPage = true,
        $page = null,
        $fontName = 'helvetica') {

        $pageId = 'p.';
        // bookmark the Title if wanted
        if ($bookmarkTitle) $this->Bookmark($titre, 0, -1);

        // display the Title with the good Font size
        $this->SetFont($fontName, '', $sizeTitle);
        $this->Cell(0, 5, $titre, 0, 1, 'C');

        // set the good Font size for the bookmarks
        $this->SetFont($fontName, '', $sizeBookmark);
        $this->Ln(10);

        // get the number of bookmarks
        $size=sizeof($this->outlines);

        // get the size of the "P. xx" cell
        $pageCellSize=$this->GetStringWidth($pageId.$this->outlines[$size-1]['p'])+2;

        // Foreach bookmark
        for ($i=0;$i<$size;$i++) {
            // if we need a new page => add a new page
            if ($this->getY()+$this->FontSize>=($this->h - $this->bMargin)) {
                $obj->_INDEX_NewPage($page);
                $this->SetFont($fontName, '', $sizeBookmark);
            }

            // Offset of the current level
            $level=$this->outlines[$i]['l'];
            if($level>0) $this->Cell($level*8);

            // Caption (cut to fit on the width page)
            $str=$this->outlines[$i]['t'];
            $strsize=$this->GetStringWidth($str);
            $availableSize=$this->w-$this->lMargin-$this->rMargin-$pageCellSize-($level*8)-4;
            $restStr = '';
            while ($strsize>=$availableSize) {
                $str=substr($str, 0, -1);
                $restStr=substr($str, -1).$restStr;
                $strsize=$this->GetStringWidth($str);
                $restStrsize=$this->GetStringWidth($restStr);
            }
            if(strlen($restStr) > 0) $hi = 0; else $hi = 2;

            // if we want to display the page nmber
            if ($displayPage) {
                // display the Bookmark Caption
                $this->Cell($strsize+2, $this->FontSize+2, $str);

                //Filling dots
                $w=$this->w-$this->lMargin-$this->rMargin-$pageCellSize-($level*8)-($strsize+2);
                $nb=$w/$this->GetStringWidth('.');
                $dots=str_repeat('.', $nb);
                $this->Cell($w, $this->FontSize+$hi, $dots, 0, 0, 'R');

                //Page number
                $this->Cell($pageCellSize, $this->FontSize+$hi, $pageId.$this->outlines[$i]['p'], 0, 1, 'R');
            } else {
                // display the Bookmark Caption
                $this->Cell($strsize+2, $this->FontSize+$hi, $str, 0, 1);
            }
            if(strlen($restStr) > 0) {
                // ligne 2
                if($level>0) $this->Cell($level*8);
                $this->Cell($restStrsize+2, $this->FontSize+2, $restStr, 0, 1);
            }
            $strTest = 'BONJOUR';
            $this->Cell($this->GetStringWidth($strTest), $this->FontSize+2, $strTest, 0, 1);
        }
    }


}
