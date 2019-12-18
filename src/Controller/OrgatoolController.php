<?php
namespace App\Controller;

use App\Controller\Component\StringComponent;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Mailer\Email;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class OrgatoolController extends AppController
{

    public function beforeFilter(Event $event) {
        
        parent::beforeFilter($event);
        $this->Workshop = TableRegistry::getTableLocator()->get('Workshops');

        $this->connection = ConnectionManager::get('default');
        $this->AppAuth->allow([
            'ajaxGetAllWorkshopsForMap',
            'ajaxGetWorkshopDetail',
            'home',
            'cluster',
            'detail',
            'all'
        ]);
        
        $this->Orgatool = TableRegistry::getTableLocator()->get('Orgatool');
    }
    
    public function isAuthorized($user)
    {
        
        if ($this->request->getParam('action') == 'verwalten') {
            if (!($this->AppAuth->isAdmin() || $this->AppAuth->isOrga())) {
                return false;
            }
            return true;
        }
        
        
        // die action "edit" ist für alle eingeloggten user erlaubt, die orga-mitglieder der initiative sind
        if ($this->request->getParam('action') == 'add') {
            
            if ($this->AppAuth->isAdmin()) {
                $this->useDefaultValidation = true;
                return true;
            }
            
            if ($this->AppAuth->isOrga()) {
                return true;
            }
            
            return false;
            
        }
        
        if ($this->request->getParam('action') == 'organize') {
            
            if (!($this->AppAuth->isOrga() || $this->AppAuth->isAdmin())) {
                return false;
            }
            
            if ($this->AppAuth->isAdmin()) {
                $this->useDefaultValidation = false;
                return true;
            }
            
            $workshopUid = (int) $this->request->getParam('pass')[0];
            
            // all approved orgas are alloewed to edit and add workshops
            $this->Workshop = TableRegistry::getTableLocator()->get('Workshops');
            
            $workshop = $this->Workshop->getWorkshopForIsUserInOrgaTeamCheck($workshopUid);
            if ($this->Workshop->isUserInOrgaTeam($this->AppAuth->user(), $workshop)) {
                return true;
            }
            
            return false;
        }
        
        return parent::isAuthorized($user);
        
    }
    
    public function _add($uid)
    {
        $orgatool = $this->Orgatool->newEntity(
            ['workshop_uid' => $uid],
            ['enabled' => APP_OFF]
        );
        return $orgatool;
    }
    
    public function organize($uid)
    {
        if ($uid === null) {
            throw new NotFoundException;
        }

        $workshop = $this->Workshop->find('all', [
            'conditions' => [
                'Workshops.uid' => $uid,
                'Workshops.status >= ' . APP_DELETED
            ]
        ])->first();
        
        if (empty($workshop)) {
            throw new NotFoundException;
        }

        $orgatool = $this->Orgatool->find('all', [
            'conditions' => [
                'Orgatool.workshop_uid' => $uid
            ]
        ])->first();

        if (empty($orgatool)) {
            $orgatool = $this->_add($uid);
        }
        
        //$this->setIsCurrentlyUpdated($orgatool->uid);
        $this->set('metaTags', ['title' => 'Initiative organisieren']);
        $this->_organize($workshop, $orgatool, true);
    }
    
    private function _organize($workshop, $orgatool, $isEditMode)
    {
                
        $this->User = TableRegistry::getTableLocator()->get('Users');
        $this->Workshop = TableRegistry::getTableLocator()->get('Workshops');
        $this->Orgatool = TableRegistry::getTableLocator()->get('Orgatool');
        $this->Orgatool_responses = TableRegistry::getTableLocator()->get('Orgatool_responses');
        
        $this->set('uid', $orgatool->uid);
        
        $this->setReferer();
        
        if (!empty($this->request->getData())) {
            
            if ($this->request->getData('Orgatool.helper_invitation_days') <= $this->request->getData('Orgatool.helper_reminder_days')) {
                $this->AppFlash->setFlashError("Tag der Erinnerung darf nicht vor der Einladung liegen!");
            }
                
            //$patchedEntity = $this->Orgatool->getPatchedEntityForAdminEdit($orgatool, $this->request->getData(), $this->useDefaultValidation);

            $patchedEntity = $this->Orgatool->patchEntity($orgatool, $this->request->getData());
            
            $errors = $patchedEntity->getErrors();
            
            if (empty($errors)) {
                
                $patchedEntity = $this->patchEntityWithCurrentlyUpdatedFields($patchedEntity);
                $entity = $this->stripTagsFromFields($patchedEntity, 'Orgatool');
                
                if ($this->Orgatool->save($entity)) {
                    $this->AppFlash->setFlashMessage($this->Workshop->name_de . ' erfolgreich gespeichert.');                    
                    $this->redirect($this->request->getData()['referer']);
                } else {
                    $this->AppFlash->setFlashError($this->Workshop->name_de . ' <b>nicht</b>erfolgreich gespeichert.');
                }
                
            } else {
                $orgatool = $patchedEntity;
            }
        }

        $this->set('workshop', $workshop);
        $this->set('orgatool', $orgatool);
        $this->set('isEditMode', $isEditMode);
        $this->set('useDefaultValidation', $this->useDefaultValidation);
        
        if (!empty($errors)) {
            $this->render('organize');
        }
    }
}
?>