<?php

/* First of all this relies on the PECL "zip" package.
  sudo pecl install zip
  then adding the extension to php.ini and restarting apache worked for me.
  The CakePHP Component code with my minor tweaks to avoid some errors in the logs is pasted below.
  Once you have added that file to your app you just need to add (or update) the controller you want to use this component in with:
  
  public $components = array('Zip');
 
  Then, you can use the component function with $this->Zip->functionName
  For example to create a new, empty zipfile in /tmp/ called test.zip:
  
  $this->Zip->begin("/tmp/test.zip");
 
  Now lets say we want to add a file in /home/andy/ called note.txt to the new
  zip but we want to add this file into a subfolder inside the zip but in a subfolder "folder/note.txt"
  
  $this->Zip->addFile("/home/andy/note.txt", "folder/note.txt");
 
  Thats it. You should have a /tmp/test.zip file which contains note.txt in a subdirectory named "folder"
  Create the file: app/Controller/Component/ZipComponent.php:
 
  Test in CakePHP 2.5.x is OK
 */
App::uses('Component', 'Controller');

class ZipComponent extends Component {

    public $components = array();
    public $settings = array();
    public $Controller = null;
    public $zip = null;

    public function __construct(ComponentCollection $collection, $settings = array()) {
        parent::__construct($collection, $settings);
        // Load in PHP extension ZIP...?
        if(extension_loaded('zip')==true){
            $this->zip = new ZipArchive();
            $this->Controller = $collection->getController();
        }else{
            throw new MissingComponentException(__('Error: Not Load extension "ZIP" in PHP.INI!!'));
        }
    }

    /**
     * 
     * @param type $function
     * @return type
     */
    public function __get($function) {
        return $this->zip->{$function};
    }

    /**
     * startup
     * @param type $controller
     */
    public function startup(&$controller) {
        
    }

    /**
     * begin
     * @return boolean
     * @params string, boolean  
     * $path : local path for zip
     * $overwrite :    
     * usage :    
     */
    public function begin($path = '', $overwrite = true) {
        $overwrite = ($overwrite) ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE;
        return $this->zip->open($path, $overwrite);
    }

    /**
     * close
     * @return boolean
     */
    public function close() {
        return $this->zip->close();
    }

    /**
     * End
     * @return type
     */
    public function end() {
        return $this->close();
    }

    /**
     * addFile
     * @return boolean  
     * @params string, string (optional)
     * $file : file to be included (full path)
     * $localFile : name of file in zip, if different  
     */
    public function addFile($file, $localFile = null) {
        return $this->zip->addFile($file, (is_null($localFile) ? $file : $localFile));
    }

    /**
     * addByContent
     * @return boolean  
     * @params string, string
     * $localFile : name of file in zip
     * $contents : contents of file
     * usage : $this->Zip->addByContents('myTextFile.txt', 'Test text file');  
     */
    public function addByContent($localFile, $contents) {
        return $this->zip->addFromString($localFile, $contents);
    }

    /**
     * @return boolean
     * @params string, string
     */
    public function addDirectory($directory, $as) {
        if ( substr($directory, -1, 1) != DS ) {
            $directory = $directory . DS;
        }
        if ( substr($as, -1, 1) != DS ) {
            $as = $as . DS;
        }
        if ( is_dir($directory) ) {
            if ( $handle = opendir($directory) ) {
                while ( false !== ($file = readdir($handle)) ) {
                    if ( is_dir($directory . $file . DS) ) {
                        if ( $file != '.' && $file != '..' ) {
                            //$this->addFile($directory.$file, $as.$file);
                            $this->addDirectory($directory . $file . DS, $as . $file . DS);
                        }
                    } else {
                        $this->addFile($directory . $file, $as . $file);
                    }
                }
                closedir($handle);
            } else {
                return false;
            }
        } else {
            return false;
        }
        return true;
    }

    public function addDir($directory, $as) {
        $this->addDirectory($directory, $as);
    }

    /**
     * @return boolean
     * @params mixed
     * $mixed : undo changes to an archive by index(int), name(string), all ('all' | '*' | blank)
     * usage : 
     * $this->Zip->undo(1);
     * $this->Zip->undo('myText.txt');
     * $this->Zip->undo('*');
     * $this->Zip->undo('myText.txt, myText1.txt');
     * $this->Zip->undo(array(1, 'myText.txt'));
     */
    public function undo($mixed = '*') {
        if ( is_array($mixed) ) {
            foreach ( $mixed as $value ) {
                $constant = is_string($value) ? 'Name' : 'Index';
                if ( !$this->zip->unchange{$constant}($value) ) {
                    return false;
                }
            }
        } else {
            $mixed = explode(',', $mixed);
            if ( in_array($mixed[ 0 ], array( '*', 'all' )) ) {
                if ( !$this->zip->unchangeAll() ) {
                    return false;
                }
            } else {
                foreach ( $mixed as $name ) {
                    if ( !$this->zip->unchangeName($name) ) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * @return boolean
     * @params mixed, string (optional)
     */
    public function rename($old, $new = null) {
        if ( is_array($old) ) {
            foreach ( $old as $cur => $new ) {
                $constant = is_string($cur) ? 'Name' : 'Index';
                if ( !$this->zip->rename{$constant}($ur, $new) ) {
                    return false;
                }
            }
        } else {
            $constant = is_string($old) ? 'Name' : 'Index';
            if ( !$this->zip->rename{$constant}($old, $new) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return index, name or FALSE
     * @params mixed, mixed (FL_NODIR, FL_NOCASE)
     */
    public function find($mixed, $options = 0) {
        if ( is_string($mixed) ) {
            return $this->zip->locatename($mixed, $options);
        } else {
            return $this->zip->getNameIndex($mixed);
        }
    }

    /**
     * @return boolean
     * @params mixed
     * $mixed : undo changes to an archive by index(int), name(string), all ('all' | '*' | blank)
     */
    public function delete($mixed) {
        if ( is_array($mixed) ) {
            foreach ( $mixed as $value ) {
                $constant = is_string($value) ? 'Name' : 'Index';
                if ( !$this->zip->delete{$constant}($value) ) {
                    return false;
                }
            }
        } else {
            $mixed = explode(',', $mixed);
            foreach ( $mixed as $value ) {
                $constant = is_string($value) ? 'Name' : 'Index';
                if ( !$this->zip->delete{$constant}($value) ) {
                    return false;
                }
            }
        }
    }

    /**
     * @return boolean
     * @params mixed, string
     * $mixed : comment by index(int), name(string), entire archive ('archive')
     */
    public function comment($mixed = 'archive', $comment) {
        if ( is_array($mixed) ) {
            //unsupported currently
        } else {
            if ( low($mixed) === 'archive' ) {
                return $this->zip->setArchiveComment($comment);
            } else {
                $constant = is_string($mixed) ? 'Name' : 'Index';
                return $this->zip->setComment{$constant}($comment);
            }
        }
    }

    /**
     * stats
     * @param String $mixed
     * @return int
     */
    public function stats($mixed) {
        $constant = is_string($mixed) ? 'Name' : 'Index';
        return $this->zip->stat{$constant}();
    }

    /**
     * extract
     * @return boolean
     * @params string, mixed
     * $entries : single name or array of names to extract, null to extract all
     */
    public function extract($location, $entries = null) {
        return $this->zip->extract($location, $entries);
    }

    /**
     * unzip
     * @param type $location
     * @param type $entries
     */
    public function unzip($location, $entries = null) {
        $this->extract($location, $entries);
    }
    
    /**
     * crearZip
     * @param type $filesIn
     * @param type $nameFileOut
     * @param string $pathTmp
     * @param type $download
     * @return boolean
     * @throws NotFoundException
     * 
     * Ejemplo de Uso:
     * $path = WWW_ROOT.'files'.DS;
     * $path2 = WWW_ROOT.'img'.DS;
     * $files=array(
     *      array('path'=>$path, 'file'=>'art-original-error-test.xls'),
     *      array('path'=>$path, 'file'=>'art-original.xls'),
     *      array('path'=>$path2, 'file'=>'glyphicons-halflings-white.png'),
     *  );
     *  // Crear zip y forzar la descarga
     *  $out =  $this->crearZip($files, 'Exportar-'.date('YmdHis').'.zip', TMP.'zips'.DS, true);
     *  if($out==false){
     *     exit('Error created zip');
     *  }else{
     *     return $out;
     *  }
     */
    public function crearZip($filesIn = array(), $nameFileOut = 'Exportacion.zip', $pathTmp=null, $download=false ) {
        // Existen los datos requeridos..?
        if(count($filesIn)>=1 && isset($filesIn[0]['path'],$filesIn[0]['file'])==True && strlen($nameFileOut)>='5'){            
            // El path es NULL ..?
            if(  is_null($pathTmp)==true){
                $pathTmp = TMP.'zips'.DS;
            }
            // No existe el path dentro del server..?
            if(file_exists($pathTmp) == false){
                // Intentamos crearlo, fallo al crear el archivo..?
                if(mkdir($pathTmp, 0775) == false){
                    throw new NotFoundException(__("Error:No se puedo crear el directorio '{$pathTmp}'"));
                    return false;
                }
            }
            // Nombre y path del archivo temporal
            $fileZipNuevo = $pathTmp.$nameFileOut;
            // Crear Zip temporal en el server
            $this->begin($fileZipNuevo);
            // Loop archivos
            foreach ( $filesIn as $file ) {
                if(isset($file['path'],$file['file'])==True){
                    $this->addFile($file['path'].$file['file'], $file['file']);
                }else{
                    // Error porque el path y name son requeridos
                    $this->close();
                    return false;
                }
            }
            $this->close();
            // Than forcing download ..?
            if($download==true){
                $this->Controller->response->file(
                    $fileZipNuevo,
                    array('download' => true, 'name' => $nameFileOut)
                );
                $this->autoRender = false;
                return $this->Controller->response;
            }else{
                // return name file created
                return $nameFileOut;
            }
        }else{
            return false;
        }
    }

}

?>