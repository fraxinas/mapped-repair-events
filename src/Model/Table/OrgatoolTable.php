<?php
namespace App\Model\Table;

use Cake\Validation\Validator;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

class OrgatoolTable extends AppTable
{

    public $name_de = '';

    public $allowedBasicHtmlFields = [
        'helper_invitation_text',
        'helper_reminder_text'
    ];

    public function initialize(array $config)
    {
        parent::initialize($config);
        
        $this->belongsTo('Workshops', [
            'foreignKey' => 'workshop_uid'
        ]);
    }
    
    public function validationDefault(Validator $validator)
    {
        $validator = $this->validationAdmin($validator);       
        return $validator;
    }
    
    public function validationAdmin(Validator $validator)
    {
        $validator = $this->getNumberRangeValidator($validator, 'helper_invitation_days', 2, 60);
        $validator = $this->getNumberRangeValidator($validator, 'helper_reminder_days', 1, 30);
        $validator = parent::addUrlValidation($validator);
        $validator->notEmptyString('helper_invitation_text', 'Bitte trage einen Einladungstext ein.');
        $validator->notEmptyString('helper_reminder_text', 'Bitte trage einen Erinnerungstext ein.');
        return $validator;
    }
}
?>