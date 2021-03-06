<?php

namespace Admin\Controller;

use App\Controller\Component\StringComponent;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Filesystem\Folder;
use Intervention\Image\ImageManagerStatic as Image;

class InternController extends AdminAppController
{

    public function isAuthorized($user)
    {
        
        if (in_array($this->request->getParam('action'), [
            'ajaxCancelAdminEditPage',
            'ajaxMiniUploadFormDeleteImage',
            'ajaxMiniUploadFormRotateImage',
            'ajaxMiniUploadFormSaveUploadedImage',
            'ajaxMiniUploadFormSaveUploadedImagesMultiple',
            'ajaxMiniUploadFormTmpImageUpload',
            'ajaxChangeAppObjectStatus'
        ])) {
            if ($this->AppAuth->user()) {
                return true;
            }
        } else {
            return parent::isAuthorized($user);
        }
    }
    
    public function addCategory($name)
    {
        $categories = TableRegistry::getTableLocator()->get('Categories');
        $c = $categories->save($categories->newEntity(['name' => $name, 'icon' => StringComponent::slugify($name)]));
        pr($c);
        exit;
    }

    public function addSubCategory($name, $parentId)
    {
        $categories = TableRegistry::getTableLocator()->get('Categories');
        $c = $categories->save($categories->newEntity([
            'name' => $name,
            'parent_id' => $parentId,
            'icon' => StringComponent::slugify($name)
        ]));
        pr($c);
        exit;
    }
    
    private function getExtension($mimeType)
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif'
        ];
        if (isset($extensions[$mimeType])) {
            return $extensions[$mimeType];
        } else {
            throw new Exception('mime type not supported');
        }
        
    }

    public function ajaxMiniUploadFormTmpImageUpload()
    {
        $this->autoRender = false;
        
        // check if uploaded file is image file
        $formatInfo = getimagesize($this->request->getData('upload.tmp_name'));
        
        // non-image files will return false
        if ($formatInfo === false || ! in_array($formatInfo['mime'], [
            'image/jpeg',
            'image/png',
            'image/gif'
        ])) {
            $message = 'Die hochgeladene Datei muss im Format "jpg", "gif" oder "png" sein.';
            die(json_encode([
                'status' => 0,
                'msg' => $message
            ]));
        }
        
        $extension = $this->getExtension($formatInfo['mime']);
        $filename = StringComponent::createRandomString(10) . '.' . $extension;
        $filenameWithPath = Configure::read('AppConfig.tmpUploadImagesDir') . '/' . $filename;
        Image::make($this->request->getData('upload.tmp_name'))
            ->widen(Configure::read('AppConfig.tmpUploadFileSize'))
            ->save(WWW_ROOT . $filenameWithPath);
        
        die(json_encode([
            'status' => 1,
            'filename' => $filenameWithPath
        ]));
    }

    public function ajaxMiniUploadFormRotateImage()
    {
        $this->autoRender = false;
        
        // check if uploaded file is image file
        $uploadedFile = $_SERVER['DOCUMENT_ROOT'] . $this->request->getData('filename');
        
        $direction = $this->request->getData('direction');
        $formatInfo = getimagesize($uploadedFile);
        
        // non-image files will return false
        if ($formatInfo === false || ! in_array($formatInfo['mime'], [
            'image/jpeg',
            'image/png',
            'image/gif'
        ])) {
            $message = 'Die hochgeladene Datei muss im Format "jpg", "gif" oder "png" sein.';
            die(json_encode([
                'status' => 1,
                'msg' => $message
            ]));
        }
        
        $directionInDegrees = null;
        if ($direction == 'CW') {
            $directionInDegrees = 90;
        }
        if ($direction == 'ACW') {
            $directionInDegrees = -90;
        }
        if (is_null($directionInDegrees)) {
            $message = 'direction wrong';
            die(json_encode(array(
                'status' => 0,
                'msg' => $message
            )));
        }
        
        Image::make($uploadedFile)
            ->rotate($directionInDegrees)
            ->save($uploadedFile);
        
        $rotatedImageSrc = $this->request->getData('filename') . '?' . StringComponent::createRandomString(3);
        die(json_encode([
            'status' => 0,
            'rotatedImageSrc' => $rotatedImageSrc
        ]));
    }

    public function ajaxMiniUploadFormSaveUploadedImagesMultiple()
    {
        $this->autoRender = false;
        $this->Photo = TableRegistry::getTableLocator()->get('Photos');
        
        $objectUid = $this->request->getData('uid');
        $objectType = $this->request->getData('objectType');
        $files = [];
        if (! empty($this->request->getData('files'))) {
            $files = $this->request->getData('files');
        }
        
        /* START thumbs erstellen */
        $thumbSizes = Configure::read('AppConfig.thumbSizesMultiple');
        
        $oldPhotos = $this->Photo->find('all', [
            'conditions' => [
                'Photos.object_uid' => $objectUid,
                'Photos.object_type' => $objectType
            ]
        ])->toArray();
        
        foreach ($files as $file) {
            
            $filename = $file['filename'];
            $uploadedFile = $_SERVER['DOCUMENT_ROOT'] . $filename;
            $formatInfo = getimagesize($uploadedFile);
            $extension = $this->getExtension($formatInfo['mime']);
            $fileNamePlain = strtolower(StringComponent::createRandomString(10)) . '.' . $extension;
            
            $photo2save = [
                'object_uid' => $objectUid,
                'object_type' => $objectType,
                'rank' => $file['rank'],
                'name' => $fileNamePlain,
                'text' => $file['text'],
                'status' => APP_ON,
                'owner' => $this->AppAuth->getUserUid()
            ];
            $newEntity = $this->Photo->newEntity($photo2save);
            $this->Photo->save($newEntity);
            
            foreach ($thumbSizes as $thumbSize => $thumbSizeOptions) {
                
                $thumbMethod = 'getThumbs' . $thumbSize . 'ImageMultiple';
                $thumbsFileName = Configure::read('AppConfig.htmlHelper')->$thumbMethod($fileNamePlain);
                
                $targetFileAbsolute = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . $thumbsFileName);
                $path = dirname($targetFileAbsolute);
                $dir = new Folder($path, true, 0755);
                
                Image::make($_SERVER['DOCUMENT_ROOT'] . $filename)
                    ->widen($thumbSize)
                    ->save($targetFileAbsolute);
                
            } // END thumbSizes
                  
            // do not save original image, because size 800 is maximum (app.tmpUploadSize)
        }
        /* END thumbs erstellen */
        
        // delete all photos for the given objectId (physical and in database)
        foreach($oldPhotos as $oldPhoto) {
            foreach($thumbSizes as $thumbSize => $thumbSizeOptions) {
                $thumbMethod = 'getThumbs' .$thumbSize. 'ImageMultiple';
                $thumbsFileName = Configure::read('AppConfig.htmlHelper')->$thumbMethod($oldPhoto->name, 'galleries', $oldPhoto->object_uid);
                $targetFileAbsolute = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . $thumbsFileName);
                unlink($targetFileAbsolute);
            }
            $entity = $this->Photo->get($oldPhoto->uid);
            $this->Photo->delete($entity);
        }
        
        $this->AppFlash->setFlashMessage('Die Bilder wurden erfolgreich gespeichert.');
        
        die(json_encode([
            'status' => 0,
            'msg' => 'Die Bilder wurden erfolgreich gespeichert.',
            'count' => count($files)
        ]));
    }

    public function ajaxMiniUploadFormSaveUploadedImage()
    {
        $this->autoRender = false;
        
        $uid = $this->request->getData('uid');
        $objectType = $this->request->getData('objectType');
        $filename = $this->request->getData('filename');
        
        // datei in uid.extension umbenennen
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $fileNamePlain = $uid . '.' . $extension;
        
        /* START thumbs erstellen */
        $thumbSizes = Configure::read('AppConfig.thumbSizes');
        foreach ($thumbSizes as $thumbSize => $thumbSizeOptions) {
            
            $thumbMethod = 'getThumbs' . $thumbSize . 'Image';
            $thumbsFileName = Configure::read('AppConfig.htmlHelper')->$thumbMethod($fileNamePlain, $objectType);
            
            $targetFileAbsolute = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . $thumbsFileName);
            $image = Image::make($_SERVER['DOCUMENT_ROOT'] . $filename);
            
            // only users have square 150 image!
            if (isset($thumbSizeOptions['square']) && $thumbSizeOptions['square'] == 1 && preg_match('/users/', $targetFileAbsolute)) {
                $image->fit($thumbSize);
            } else {
                if ($thumbSize != 'original') {
                    $image->widen($thumbSize);
                }
            }
            
            $image->save($targetFileAbsolute);
        }
        /* END thumbs erstellen */
        
        // save original image
        $sourceFile = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . $filename);
        $targetFile = Configure::read('AppConfig.htmlHelper')->getOriginalImage($fileNamePlain, $objectType);
        $targetFile = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . $targetFile);
        rename($sourceFile, $targetFile);
        
        $fileNamePlainWithTimestamp = $fileNamePlain . '?' . time();
        
        $filePathWithTimestamp = str_replace($_SERVER['DOCUMENT_ROOT'], '/', $targetFileAbsolute) . '?' . filemtime($targetFileAbsolute);
        $filePathWithTimestamp = preg_replace('/thumbs\-' . $thumbSize . '/', 'thumbs-150', $filePathWithTimestamp);
        
        die(json_encode([
            'status' => 0,
            'filePathWithTimestamp' => $filePathWithTimestamp,
            'fileNamePlainWithTimestamp' => $fileNamePlainWithTimestamp
        ]));
    }

    /**
     * deletes both db entries and physical files (thumbs)
     * 
     * @param int $uid
     */
    public function ajaxMiniUploadFormDeleteImage($uid)
    {
        $uid = (int) $uid;
        
        if ($uid == 0 || $uid == '') {
            $message = '$uid nicht korrekt: ' . $uid;
            $this->log($message);
            die(json_encode([
                'status' => 0,
                'msg' => $message
            ]));
        }
        
        $objectType = $this->Root->getType($uid);
        $objectClass = Inflector::classify($objectType);
        $pluralizedClass = Inflector::pluralize($objectClass);
        $objectTable = TableRegistry::getTableLocator()->get($pluralizedClass);
        $object = $objectTable->find('all', [
            'conditions' => [
                $pluralizedClass . '.uid' => $uid
            ]
        ])->first();
        $fileName = explode('?', $object->image);
        $fileName = $fileName[0];
        
        // delete physical files
        foreach (Configure::read('AppConfig.thumbSizes') as $thumbSize => $thumbSizeOptions) {
            $thumbMethod = 'getThumbs' . $thumbSize . 'Image';
            $thumbsFileName = Configure::read('AppConfig.htmlHelper')->$thumbMethod($fileName, $objectType);
            $targetFileAbsolute = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . $thumbsFileName);
            unlink($targetFileAbsolute);
        }
        
        $object = $objectTable->get($object->uid);
        $entity = $objectTable->patchEntity($object, ['image' => '']);
        $objectTable->save($entity);
        
        $this->AppFlash->setFlashMessage('Das Bild wurde erfolgreich gelöscht.');
        
        $this->redirect($this->referer());
    }

    public function ajaxChangeAppObjectStatus()
    {
        Configure::write('debug', 0);
        $this->autoRender = false;
        
        $uid = $this->request->getData('uid');
        $value = $this->request->getData('value');
        $statusType = $this->request->getData('status_type');
        
        $objectType = $this->Root->getType($uid);
        $objectClass = Inflector::classify($objectType);
        $pluralizedClass = Inflector::pluralize($objectClass);
        $this->{$objectClass} = TableRegistry::getTableLocator()->get($pluralizedClass);
        
        $entity = $this->$objectClass->get($uid, [
                'conditions' => [
                    $pluralizedClass.'.status >= ' . APP_DELETED
                ]
            ]
        );
        if ($objectType == 'users') {
            $entity->revertPrivatizeData();
        }
        $entity = $this->$objectClass->patchEntity($entity, [$statusType => $value]);
        if ($this->$objectClass->save($entity)) {
            die(json_encode([
                'status' => 0,
                'msg' => 'ok'
            ]));
        } else {
            die(json_encode([
                'status' => 1,
                'msg' => 'delete did not work'
            ]));
        }
    }

    public function ajaxCancelAdminEditPage()
    {
        Configure::write('debug', 0);
        $this->autoRender = false;
        
        $uid = (int) $this->request->getData('uid');
        
        if ($uid > 0) {
        
            $objectType = $this->Root->getType($uid);
            $objectClass = Inflector::classify($objectType);
            $pluralizedClass = Inflector::pluralize($objectClass);
            $this->{$objectClass} = TableRegistry::getTableLocator()->get($pluralizedClass);
            
            $object = $this->$objectClass->find('all', [
                'conditions' => [
                    $pluralizedClass . '.uid' => $uid,
                    $pluralizedClass . '.status >= ' . APP_DELETED
                ]
            ])->first();
            
            // eigene bearbeitungs-hinweise bei click auf cancel löschen
            if ($object->currently_updated_by == $this->AppAuth->getUserUid()) {
                $entity = $this->$objectClass->patchEntity($object, [
                    'currently_updated_by' => 0
                ]);
                $this->$objectClass->save($entity);
            }
        }
        
        $referer = $this->request->getData('referer');
        if ($referer == '') {
            $referer = '/';
        }
        
        die(json_encode([
            'status' => 0,
            'msg' => 'ok',
            'referer' => $referer
        ]));
    }
    
}
?>