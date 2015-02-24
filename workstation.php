<?php

	require('config.php');
	require('./class/asset.class.php');
	require('./class/ordre_fabrication_asset.class.php');
	
	$langs->load('asset@asset');
	
	dol_include_once('/core/class/html.form.class.php');
	
	
	$action=__get('action','list');
	$fk_product=__get('fk_product',0,'integer');;
	
	$ATMdb=new TPDOdb;
	
	llxHeader('',$langs->trans('workstation'),'','');
	
	if($fk_product>0) {
		
		switch($action) {
			
			case 'add':
				$fk_asset_workstation = __get('fk_asset_workstation',0,'int');
				
				$wsp = new TAssetWorkstationProduct;
				$wsp->fk_product = $fk_product;
				$wsp->fk_asset_workstation = $fk_asset_workstation;
				
				$ws = new TAssetWorkstation;
				$ws->load($ATMdb,$fk_asset_workstation);
				
				$wsp->nb_hour_prepare = $ws->nb_hour_prepare;
				$wsp->nb_hour_manufacture = $ws->nb_hour_manufacture;
				$wsp->nb_hour = $ws->nb_hour_prepare + $ws->nb_hour_manufacture;
				
				$wsp->save($ATMdb);
			
				setEventMessage('Poste de travail ajouté');
			
				_liste_link($ATMdb, $fk_product);
				
				break;
			
			case 'list':
				_liste_link($ATMdb, $fk_product);
				
				break;
			
			case 'save':
				//var_dump($_REQUEST['TAssetWorkstationProduct']);
				foreach($_REQUEST['TAssetWorkstationProduct'] as $id=>$row) {
				
					$wsp = new TAssetWorkstationProduct;
					//$ATMdb->debug=true;
					$wsp->load($ATMdb, $id);
					
					$wsp->nb_hour_prepare = Tools::string2num($row['nb_hour_prepare']);
					$wsp->nb_hour_manufacture = Tools::string2num($row['nb_hour_manufacture']);
					$wsp->nb_hour = $wsp->nb_hour_prepare + $wsp->nb_hour_manufacture;
					$wsp->rang = (double) $row['rang'];
					
					$wsp->save($ATMdb);
				}
				
				setEventMessage('Modifications enregistrées');
				
				_liste_link($ATMdb, $fk_product);
				break;
			
			case 'delete':				
				$wsp = new TAssetWorkstationProduct;
				$wsp->load($ATMdb, GETPOST('id_wsp'));
				$wsp->to_delete = true;
				$wsp->save($ATMdb);
				
				_liste_link($ATMdb, $fk_product);
				
				break;
		}
		
	}
	else {
		
		switch($action) {
			
			case 'save':
				$ws=new TAssetWorkstation;
				$ws->load($ATMdb, __get('id',0,'integer'));
				$ws->set_values($_REQUEST);
				$ws->nb_hour_max = $ws->nb_hour_prepare + $ws->nb_hour_manufacture;
				$ws->save($ATMdb);
				
				_fiche($ATMdb, $ws);
				
				break;
			case 'view':
				$ws=new TAssetWorkstation;
				$ws->load($ATMdb, __get('id',0,'integer'));
				
				_fiche($ATMdb, $ws);
				
				break;
			
			case 'edit':
				$ws=new TAssetWorkstation;
				$ws->load($ATMdb, __get('id',0,'integer'));
				_fiche($ATMdb, $ws,'edit');
				
				break;
			
			case 'delete':
			
				$ws=new TAssetWorkstation;
				$ws->load($ATMdb, __get('id',0,'integer'));
				
				$ws->delete($ATMdb);
				
				_liste($ATMdb);
				
				break;
			
			case 'new':
				
				$ws=new TAssetWorkstation;
				$ws->set_values($_REQUEST);
				
				_fiche($ATMdb, $ws,'edit');
				
				break;
			
			case 'list':
				
				_liste($ATMdb);
				
				break;
			
			
		}
		
	}
	
	
	llxFooter();

function _liste_link(&$ATMdb, $fk_product) {
	global $db,$langs,$conf, $user;	
	
	if($fk_product>0){
		if(is_file(DOL_DOCUMENT_ROOT."/lib/product.lib.php")) require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
		else require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
		
		require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
			
		$product = new Product($db);
		$result=$product->fetch($fk_product);	
			
		$head=product_prepare_head($product, $user);
		$titre=$langs->trans("CardProduct".$product->type);
		$picto=($product->type==1?'service':'product');
		dol_fiche_head($head, 'tabOF1', $titre, 0, $picto);
	}
	
	$form=new TFormCore('auto','formLWS');
	echo $form->hidden('action', 'save');
	echo $form->hidden('fk_product', $fk_product);
	
	
	$l=new TListviewTBS('listWS');

	$sql= "SELECT wsp.rowid as id, wsp.fk_asset_workstation as id_ws, ws.libelle, wsp.rang, wsp.nb_hour_prepare, wsp.nb_hour_manufacture, wsp.nb_hour, '' as 'action'
	
	FROM ".MAIN_DB_PREFIX."asset_workstation ws LEFT OUTER JOIN ".MAIN_DB_PREFIX."asset_workstation_product wsp ON (wsp.fk_asset_workstation=ws.rowid)
	 
	WHERE entity=".$conf->entity."
	 AND wsp.fk_product=".$fk_product;

	$liste =  $l->render($ATMdb, $sql,array(
	
		'link'=>array(
			'libelle'=>'<a href="?action=view&id=@id_ws@">@val@</a>'
			,'rang'=>'<input type="text" name="TAssetWorkstationProduct[@id@][rang]" value="@val@" size="5" />'
			,'nb_hour_prepare'=>'<input type="text" name="TAssetWorkstationProduct[@id@][nb_hour_prepare]" value="@val@" size="5" />'
			,'nb_hour_manufacture'=>'<input type="text" name="TAssetWorkstationProduct[@id@][nb_hour_manufacture]" value="@val@" size="5" />'
			//,'nb_hour'=>'<input type="text" name="TAssetWorkstationProduct[@id@][nb_hour]" value="@val@" size="5" />'
			,'nb_hour'=>'@val@'
			,'action'=> '<a href="workstation.php?action=delete&fk_product='.$fk_product.'&id_wsp=@id@">'.img_picto('Supprimer', 'delete.png').'</a>'
		)
		,'title'=>array(
			'nb_hour_prepare'=>"Nombre d'heures de préparation"
			,'nb_hour_manufacture'=>"Nombre d'heures de fabrication"
			,'nb_hour'=>"Nombre d'heures"
			,'rang'=>"Rang"
		)
		,'hide'=>array('id_ws')
	
	));
	
	
	$TBS=new TTemplateTBS;
	
	print $TBS->render('./tpl/workstation_link.tpl.php',
		array()
		,array(
			'view'=>array(
				'mode'=>$mode
				,'liste'=>$liste
				,'select_workstation'=>$form->combo('', 'fk_asset_workstation', TAssetWorkstation::getWorstations($ATMdb), -1)
				,'fk_product'=>$fk_product
			)
		)
		
	);
	
	$form->end();
}


function _fiche(&$ATMdb, &$ws, $mode='view') {
	global $db;

	$TBS=new TTemplateTBS;
	
	$form=new TFormCore('auto', 'formWS', 'post', true);
	
	$form->Set_typeaff( $mode );
	
	echo $form->hidden('action','save');
	echo $form->hidden('id',$ws->getId());
	
	$formDoli=new Form($db);
	
	$TForm=array(
		'libelle'=>$form->texte('', 'libelle', $ws->libelle,80,255)
		,'nb_hour_prepare'=>$form->texte('', 'nb_hour_prepare', $ws->nb_hour_prepare,3,3)
		,'nb_hour_manufacture'=>$form->texte('', 'nb_hour_manufacture', $ws->nb_hour_manufacture,3,3)
		,'nb_hour_max'=>$ws->nb_hour_max
		,'fk_usergroup'=>$formDoli->select_dolgroups($ws->fk_usergroup, 'fk_usergroup',0,'', ($mode=='view') ? 1 : 0 )
		,'id'=>$ws->getId()
	);
	
	print $TBS->render('./tpl/workstation.tpl.php',array(),array(
			'ws'=>$TForm
			,'view'=>array(
				'mode'=>$mode
			)
		)
		
	);
	
	$form->end();
}

function _liste(&$ATMdb) {
	global $conf, $langs;
	/*
	 * Liste des poste de travail de l'entité
	 */
	
	$l=new TListviewTBS('listWS');

	$sql= "SELECT ws.rowid as id, ws.libelle, ws.fk_usergroup, ws.nb_hour_prepare, ws.nb_hour_manufacture, ws.nb_hour_max 
	
	FROM ".MAIN_DB_PREFIX."asset_workstation ws LEFT OUTER JOIN ".MAIN_DB_PREFIX."asset_workstation_product wsp ON (wsp.fk_asset_workstation=ws.rowid)
	 LEFT OUTER JOIN ".MAIN_DB_PREFIX."asset_workstation_of wsof ON (wsof.fk_asset_workstation=ws.rowid)
	 
	WHERE entity=".$conf->entity.' GROUP BY ws.rowid';

	$fk_product = __get('id_product',0,'integer');
	if($fk_product>0)$sql.=" AND wsp.fk_product=".$fk_product;

	$fk_assetOF = __get('id_assetOF',0,'integer');
	if($fk_assetOF>0)$sql.=" AND wsp.fk_assetOF=".$fk_assetOF;


	print $l->render($ATMdb, $sql,array(
	
		'link'=>array(
			'libelle'=>'<a href="?action=view&id=@id@">@val@</a>'
		)
		,'title'=>array(
			'nb_hour_prepare'=>"Nombre d'heure de preparation",
			'nb_hour_manufacture'=>"Nombre d'heure de fabrication",
			'nb_hour_max'=>"Nombre d'heure maximum",
			'id'=>"Id",
			'libelle'=>"Intitulé poste de travail",
			'fk_usergroup'=>"Groupe"
		)
		,'liste'=>array(
			'titre'=>'Liste des '.$langs->trans('workstation')
			,'image'=>img_picto('','title.png', '', 0)
			,'picto_precedent'=>img_picto('','back.png', '', 0)
			,'picto_suivant'=>img_picto('','next.png', '', 0)
			,'noheader'=> (int)isset($_REQUEST['fk_soc']) | (int)isset($_REQUEST['fk_product'])
			,'messageNothing'=>"Il n'y a aucun ".$langs->trans('Workstation')." à afficher"
			,'picto_search'=>img_picto('','search.png', '', 0)
		)
	
	));
	
}
