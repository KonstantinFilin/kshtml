<?php

    namespace KsHtml\Form\Element;

    class UploadedFile
    {
        protected $name;
        protected $type;
        protected $tmpName;
        protected $error;
        protected $size;
        protected $errorMessages;
        protected $uploadDir;

        function __construct($fileInfo, $uploadDir)
        {
            $this->name = $fileInfo["name"];
            $this->type = $fileInfo["type"];
            $this->tmpName = $fileInfo["tmp_name"];
            $this->error = $fileInfo["error"];
            $this->size = $fileInfo["size"];
            $this->uploadDir = $uploadDir;

            $this->errorMessages = array(
                UPLOAD_ERR_CANT_WRITE => "Каталог загрузки недоступен для записи",
                UPLOAD_ERR_EXTENSION => "Сервер не поддерживает загрузку этого типа файлов",
                UPLOAD_ERR_FORM_SIZE => "Слишком большой файл (ограничение формы)",
                UPLOAD_ERR_INI_SIZE => "Слишком большой файл (ограничение сервера)",
                UPLOAD_ERR_NO_FILE => "Файл не был загружен",
                UPLOAD_ERR_NO_TMP_DIR => "Не могу найти временный каталог",
                UPLOAD_ERR_PARTIAL => "Файл загружен не до конца"
            );
            
            $this->checkUploadDirIsWriteable();
        }

        public function getName()
        {
            return $this->name;
        }

        public function getType()
        {
            return $this->type;
        }

        public function getTmpName()
        {
            return $this->tmpName;
        }

        public function hasError()
        {
            return $this->error != UPLOAD_ERR_OK;
        }

        public function hasErrorNoFileUploaded()
        {
            return $this->error == UPLOAD_ERR_NO_FILE;
        }

        public function getError()
        {
            return $this->errorMessages[$this->error];
        }

        public function getSize()
        {
            return $this->size;
        }

        public function getFullPath()
        {
            return $this->uploadDir . "/" . $this->getName();
        }
        
        public function setUploadDir($uploadDir) {
            $this->uploadDir = $uploadDir;
            $this->checkUploadDirIsWriteable();
        }
        
        public function getUploadDir() {
            return $this->uploadDir;
        }

        protected function checkUploadDirIsWriteable()
        {
            if(!is_writeable($this->uploadDir)) {
                $this->error = UPLOAD_ERR_CANT_WRITE;
            }
        }
    }

?>
