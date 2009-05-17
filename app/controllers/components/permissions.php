<?php
class PermissionsComponent extends Object{
	var $components = array('Auth', 'Acl');
	
	/**
	 * Check which options user can access to and returns
	 * an associative array with boolean value for each
	 * of the options.
	 * The options are : canComment, canEdit and canDelete.
	 */
	function getSentencesOptions($sentence_owner_id, $current_user_id){
		$specialOptions = array(
			  'canComment' => false
			, 'canEdit' => false
			, 'canDelete' => false
			, 'canAdopt' => false
			, 'canLetGo' => false
			, 'canTranslate' => false
		);
		
		if($this->Auth->user('id')){
			$specialOptions['canComment'] = true;
			$specialOptions['canTranslate'] = true;
			
			if($this->Auth->user('group_id') < 3){
				
				$specialOptions['canEdit'] = true;
				
			}
			if($sentence_owner_id == $current_user_id){
			
				$specialOptions['canEdit'] = true;
				$specialOptions['canLetGo'] = true;
				
			}
			
			$specialOptions['canDelete'] = ($this->Auth->user('group_id') < 2);
			
		}
		if($sentence_owner_id == NULL OR $sentence_owner_id = 0){
			
			$specialOptions['canAdopt'] = true;
			
		}
		
		return $specialOptions;
	}
}
?>