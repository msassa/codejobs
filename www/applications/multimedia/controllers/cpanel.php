<?php
/**
 * Access from index.php:
 */
if(!defined("_access")) {
	die("Error: You don't have permission to access here...");
}

class CPanel_Controller extends ZP_Controller {
	
	private $vars = array();
	
	public function __construct() {		
		$this->app("cpanel");
		
		$this->application = whichApplication();
		
		$this->CPanel = $this->classes("cpanel", "CPanel", NULL, "cpanel");
		
		$this->isAdmin = $this->CPanel->load();
		
		$this->vars = $this->CPanel->notifications();
		
		$this->CPanel_Model = $this->model("CPanel_Model");
		
		$this->Templates = $this->core("Templates");
		
		$this->Templates->theme("cpanel");
		
		$this->Model = ucfirst($this->application) ."_Model";
		
		$this->{"$this->Model"} = $this->model($this->Model);		
	}
	
	public function index() {
		if($this->isAdmin) {
			redirect("cpanel");
		} else {
			$this->login();
		}
	}

	public function check() {
		if(POST("trash") and is_array(POST("records"))) { 
			foreach(POST("records") as $record) {
				$this->trash($record, TRUE); 
			}

			redirect("$this->application/cpanel/results");
		} elseif(POST("restore") and is_array(POST("records"))) {
			foreach(POST("records") as $record) {
				$this->restore($record, TRUE); 
			}

			redirect("$this->application/cpanel/results");
		} elseif(POST("delete") and is_array(POST("records"))) {
			foreach(POST("records") as $record) {
				$this->delete($record, TRUE); 
			}

			redirect("$this->application/cpanel/results");
		}

		return FALSE;
	}

	public function delete($ID = 0, $return = FALSE) {
		if(!$this->isAdmin) {
			$this->login();
		}
		
		if($this->CPanel_Model->delete($ID)) {
			if($return) {
				return TRUE;
			}

			redirect("$this->application/cpanel/results/trash");
		} else {
			if($return) {
				return FALSE;
			}

			redirect("$this->application/cpanel/results");
		}	
	}

	public function restore($ID = 0, $return = FALSE) { 
		if(!$this->isAdmin) {
			$this->login();
		}
		
		if($this->CPanel_Model->restore($ID)) {
			if($return) {
				return TRUE;
			}

			redirect("$this->application/cpanel/results/trash");
		} else {
			if($return) {
				return FALSE;
			}

			redirect("$this->application/cpanel/results");
		}
	}

	public function trash($ID = 0, $return = FALSE) {
		if(!$this->isAdmin) {
			$this->login();
		}
		
		if($this->CPanel_Model->trash($ID)) {		
			if($return) {
				return TRUE;
			}	

			redirect("$this->application/cpanel/results");
		} else {
			if($return) {
				return FALSE;
			}

			redirect("$this->application/cpanel/add");
		}
	}
	
	public function add() {
		if(!$this->isAdmin) {
			$this->login();
		}
		
		$this->title("Add");
		
		$this->CSS("forms", "cpanel");
		$this->CSS("www/lib/scripts/js/uploader/styles.css");
		$this->js("www/lib/scripts/js/uploader/filedrag.js");

		$this->vars["uploaded"] = FALSE; 

		if(POST("upload")) {
			$this->vars["uploaded"] = TRUE; 
		}

		if(POST("save")) {
			$save = $this->{"$this->Model"}->cpanel("save");
			
			$this->vars["alert"] = $save;
		} elseif(POST("cancel")) {
			redirect("cpanel");
		}
		
		$this->vars["view"] = $this->view("add", TRUE, $this->application);
		
		$this->render("content", $this->vars);
	}
	
	public function edit($ID = 0) {
		if(!$this->isAdmin) {
			$this->login();
		}
		
		if((int) $ID === 0) { 
			redirect("$this->application/cpanel/results");
		}

		$this->title("Edit");
		
		$this->CSS("forms", "cpanel");
		$this->CSS("categories", "categories");
		
		$this->js("tiny-mce");
		$this->js("insert-html");
		$this->js("show-element");	
		
		if(POST("edit")) {
			$this->vars["alert"] = $this->{"$this->Model"}->cpanel("edit");
		} elseif(POST("cancel")) {
			redirect("cpanel");
		} 
		
		$data = $this->{"$this->Model"}->getByID($ID);
		
		if($data) {
			$this->Library 	  = $this->classes("Library", "cpanel");
			$this->Categories = $this->classes("Categories", "categories");		
			
			$this->vars["data"]				= $data;
			$this->vars["muralImage"] 		= $this->{"$this->Model"}->getMuralByID(segment(3, isLang()));
			$this->vars["muralDeleteURL"] 	= ($this->vars["muralImage"]) ? path("$this->application/cpanel/delete-mural/$ID")  : NULL;
			$this->vars["application"]		= $this->CPanel->getApplicationID($this->application);
			$this->vars["categories"]		= $this->Categories->getCategories("edit");
			$this->vars["categoriesRadio"]  = $this->Categories->getCategories("add", "radio", "parent");
			$this->vars["imagesLibrary"]    = $this->Library->getLibrary("images");
			$this->vars["documentsLibrary"] = $this->Library->getLibrary("documents");
			
			$this->Tags_Model = $this->model("Tags_Model");
			
			$this->vars["tags"] = $this->Tags_Model->getTagsByRecord(3, segment(3, isLang()), TRUE);

			$this->js("www/lib/scripts/ajax/password.js", TRUE);
			$this->js("tagsinput.min", "cpanel");
			$this->js("jquery-ui.min", "cpanel");
			$this->js("tags", "cpanel");
			
			$this->CSS("tagsinput", "cpanel");	
		
			$this->vars["view"] = $this->view("add", TRUE, $this->application);
			
			$this->render("content", $this->vars);
		} else {
			redirect("$this->application/cpanel/results");
		}
	}
	
	public function login() {
		$this->title("Login");
		$this->CSS("login", "users");
		
		if(POST("connect")) {	
			$this->Users_Controller = $this->controller("Users_Controller");
			
			$this->Users_Controller->login("cpanel");
		} else {
			$this->vars["URL"]  = getURL();
			$this->vars["view"] = $this->view("login", TRUE, "cpanel");
		}
		
		$this->render("include", $this->vars);
		$this->render("header", "footer");
		
		exit;
	}
	
	public function results() {
		if(!$this->isAdmin) {
			$this->login();
		}

		$this->check();
		
		$this->title("Manage ". ucfirst($this->application));

		$this->CSS("results", "cpanel");
		$this->CSS("pagination");
		
		$this->js("checkbox");
			
		$trash = (segment(3, isLang()) === "trash") ? TRUE : FALSE;
		
		$total 	    = $this->CPanel_Model->total($trash);
		$thead 	    = $this->CPanel_Model->thead("checkbox, ". getFields($this->application) .", Action", FALSE);
		$pagination = $this->CPanel_Model->getPagination($trash);
		$tFoot 	    = getTFoot($trash);
		
		$this->vars["message"]    = (!$tFoot) ? "Error" : NULL;
		$this->vars["pagination"] = $pagination;
		$this->vars["trash"]  	  = $trash;	
		$this->vars["search"] 	  = getSearch(); 
		$this->vars["table"]      = getTable(__(_("Manage " . ucfirst($this->application))), $thead, $tFoot, $total);					
		$this->vars["view"]       = $this->view("results", TRUE, "cpanel");
		
		$this->render("content", $this->vars);
	}
	
	public function upload($size) {
		if(!$this->isAdmin) {
			$this->login();
		}

		$this->Files = $this->core("Files");
		
		print $this->Files->uploadResource();
	}
}