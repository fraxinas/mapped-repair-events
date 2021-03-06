<?php
namespace Admin\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Cache\Cache;
use Cake\ORM\Query;
use Cake\Utility\Inflector;

class AdminAppController extends AppController
{

    public $pluralizedModelName;

    public $primaryKey;

    public $paginate;

    public $searchOptions = [];

    public $searchName = true;

    public $searchText = true;

    public $searchUid = true;

    public $searchStatus = true;
    
    public $conditions = [];
    
    public $matchings = [];

    public function isAuthorized($user)
    {
        if (! $this->AppAuth->isAdmin()) {
            return false;
        }
        return parent::isAuthorized($user);
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        
        $title = $this->name;
        if ($this->request->getParam('action') != 'index') {
            $title .= ' | ' . Inflector::camelize($this->request->getParam('action'));
        }
        $metaTags = [
            'title' => $title
        ];
        $this->set('metaTags', $metaTags);
        
        if ($this->searchText) {
            $this->searchOptions[$this->pluralizedModelName . '.text'] = [
                'name' => $this->pluralizedModelName . '.text',
                'searchType' => 'search'
            ];
        }
        if ($this->searchUid) {
            $this->searchOptions[$this->pluralizedModelName . '.uid'] = [
                'name' => $this->pluralizedModelName . '.uid',
                'searchType' => 'equal'
            ];
        }
        if ($this->searchName) {
            $this->searchOptions[$this->pluralizedModelName . '.name'] = [
                'name' => $this->pluralizedModelName . '.name',
                'searchType' => 'search'
            ];
        }
        
        if ($this->searchStatus) {
            $this->searchOptions[$this->pluralizedModelName . '.status'] = [
                'name' => $this->pluralizedModelName . '.status',
                'searchType' => 'equal',
                'extraDropdown' => true
            ];
            $this->generateSearchConditions('status');
        }
        $this->searchOptions = array_reverse($this->searchOptions);
        
        $this->generateSearchConditions('standard');
        
        $this->prepareSearchOptionsForDropdown();
    }

    public function index()
    {
        
        $this->primaryKey = $this->{$this->pluralizedModelName}->getPrimaryKey();
        
        $this->paginate['order'] = [
            $this->pluralizedModelName . '.updated' => 'DESC'
        ];
        $this->conditions[] = $this->pluralizedModelName . '.status > ' . APP_DELETED;
        
        $this->set('objectClass', Inflector::classify($this->name));
        
        $this->set('searchStatus', $this->searchStatus);
    }

    protected function generateSearchConditions($searchFieldKey)
    {
        $queryParams = $this->request->getQueryParams();
        
        if (isset($queryParams['val-' . $searchFieldKey])) {
            $filterValue = $queryParams['val-' . $searchFieldKey];
            if ($filterValue == '') {
                return;
            }
            $searchType = $this->searchOptions[
                $queryParams[
                    'key-' . $searchFieldKey
                ]
            ]['searchType'];
            switch ($searchType) {
                case 'equal':
                    $this->conditions[$queryParams['key-' . $searchFieldKey]] = $queryParams['val-' . $searchFieldKey];
                    break;
                case 'search':
                    $this->conditions[] = $queryParams['key-' . $searchFieldKey] . " LIKE '%" . $queryParams['val-' . $searchFieldKey] . "%'";
                    break;
                case 'matching':
                    $this->matchings[] = [
                        'association' => $this->searchOptions[$queryParams['key-' . $searchFieldKey]]['association'],
                        'condition' => [
                            $queryParams['key-' . $searchFieldKey] => $queryParams['val-' . $searchFieldKey]
                        ]
                    ];
                    break;
            }
        }
    }
    
    protected function addMatchingsToQuery($query)
    {
        
        foreach($this->matchings as $matching) {
            $query->matching($matching['association'], function(Query $q) use ($matching) {
                return $q->where($matching['condition']);
            });
        }
        return $query;
    }

    /**
     * validation happens in child controller
     */
    protected function saveObject($entity, $useDefaultValidation = true)
    {
        
        //header('X-XSS-Protection:0');
        $modelName = $this->modelName;
        
        $entity = $this->stripTagsFromFields($entity, $modelName);
        
        if ($this->$modelName->save($entity)) {
            $this->AppFlash->setFlashMessage($this->$modelName->name_de . ' erfolgreich gespeichert.');
            // votings und orga pads (object_groups) können die navi verändern => navi für uneingeloggte user löschen (die für eingeloggte wird eh nur 1 sec gecacht)
            Cache::delete('element_navi_0_core_navi', 'navi');
            $this->redirect($this->request->getData()['referer']);
        } else {
            $this->AppFlash->setFlashError($this->$modelName->name_de . ' wurde <b>nicht</b> gespeichert. Bitte überprüfe das Formular.');
        }
    }
    
    protected function addSearchOptions($searchOptions)
    {
        $searchOptions = array_reverse($searchOptions);
        if (empty($this->searchOptions)) {
            $this->searchOptions = $searchOptions;
        } else {
            $this->searchOptions = array_merge($this->searchOptions, $searchOptions);
        }
        $this->prepareSearchOptionsForDropdown();
    }

    private function prepareSearchOptionsForDropdown()
    {
        $searchOptionsForDropdown = $this->searchOptions;
        foreach ($searchOptionsForDropdown as $key => $searchOption) {
            if (isset($searchOption['extraDropdown']) && $searchOption['extraDropdown']) {
                unset($searchOptionsForDropdown[$key]);
            }
        }
        $this->set('searchOptionsForDropdown', $searchOptionsForDropdown);
    }
    
    

}
 