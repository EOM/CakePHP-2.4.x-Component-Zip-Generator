<?php

App::uses('AppController', 'Controller');

/**
 * Demo User Component Zip
 */
class HolaZipController extends AppController {

    /**
     * This controller does not use a model 
     *
     * @var array
     */
    public $uses = array();

    /**
     * Load App/Controller/Component/ZipComponent.php
     *
     * @var array Components 
     */
    public $components = array( 'Zip' );

    /**
     * 
     * @return String or File
     */
    public function index() {
        // Load on the fly component Zip in Action
        //if(isset($this->Zip)==false){ $this->Zip = $this->Components->load('Zip'); }
        // Init Ejemplo de Uso:
        $path = WWW_ROOT . 'files' . DS;
        //$path2 = WWW_ROOT . 'img' . DS;
        $files = array(
            array( 'path' => $path, 'file' => DS . 'SubDirDemoInZip' . DS . '1219132.png' ),
            array( 'path' => $path, 'file' => 'index_e-om_desing_group.png' ),
                //array( 'path' => $path2, 'file' => 'glyphicons-halflings-white.png' ),
        );
        $nowNameFile = 'Zip-Generator-' . date('YmdHis') . '.zip';
        // Dir Temp out Zip Generator in Server
        $pathTmp = TMP;
//        $pathTmp = TMP . 'zips' . DS;
        // Download ..?
        $download = true;
        // Create zip and force download
        $out = $this->Zip->crearZip($files, $nowNameFile, $pathTmp, $download);
        if ( $out == false ) {
            exit('Error created zip');
        } else {
            // Return String NameFile or File Download = true
            return $out;
        }
    }

}
