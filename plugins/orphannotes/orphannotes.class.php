<?php
/**
 * @package OTX
 * @copyright Centre pour L'édition Électronique Ouverte
 * @licence http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 **/

class orphannotes extends Plugin {

	private $_db;
	private $_param;
	private $_status;
	private $_config;
	private $doc_source = array();
	private $doc_tmp = array();
	private $doc_output;
	private $xml;
	private $request;
	private $iddocument;
	public $output = array();

	protected function __construct($db, $param){
		$this->_param  = $param;
		$this->_db     = $db;
		$this->_config = OTXConfig::singleton();
	}

	function run() {
		if('proposal-extended' === $this->_param['type'] || 'recompose' === $this->_param['type']) {
			$this->request = unserialize(file_get_contents($this->_param['sourcepath']));

			if(empty($this->request['iddocument'])) {
				throw new Exception($this->_status);
			}
			$this->iddocument = intval($this->request['iddocument']);

			unset($this->request['iddocument']);
			$this->rmdir(dirname($this->_param['sourcepath']));

			$doc = $this->_db->query('SELECT * FROM Document WHERE idDocument = '.(int) $this->iddocument)->fetch();

			if(empty($doc)) {
				throw new Exception($this->_status);
			}

			$this->doc_tmp = $this->getFileInfo($doc['pathDocument']);
			$this->doc_source['basename'] = $doc['realname'];

		} else {
			$realname = basename($this->_param['sourcepath']);
			$this->doc_source = $this->getFileInfo($this->_param['sourcepath']);

			$tmp_path = $this->convertDocument($this->_param['sourcepath'], 'odt');
			unlink($this->doc_source['realpath']);
			$this->doc_tmp = $this->getFileInfo($tmp_path);

			$this->_db->query("INSERT INTO Document (nbNotes, realname, pathDocument) VALUES (0, ".$this->_db->Quote($this->doc_source['basename']).", ".$this->_db->Quote($tmp_path).");");
			$this->iddocument = $this->_db->lastInsertId();

			if(! $this->iddocument) {
				throw new Exception($this->_status);
			}
		}

		$za = new ZipArchive();
		if(!$za->open($this->doc_tmp['realpath'])) {
			throw new Exception($this->_status);
		}

		if(! ($this->xml = $za->getFromName('content.xml'))) {
			throw new Exception($this->_status);
		}
		switch($this->_param['type']) {
			case 'proposal':
				return $this->_orphanNotes(false, $za);
				break;
			case 'proposal-extended':
				return $this->_orphanNotes(true, $za);
				break;
			case 'recompose':
				return $this->_orphanNotesRecomposeDocument($za);
				break;
			default:
				$this->_status = 'Unknown mode type';
				throw new Exception($this->_status);
				break;
		}
    }

	/**
	* conversion d'un document
	* ! system call inside (soffice)
	* return : le chemin vers le document convertit
	**/
	protected function convertDocument($sourcepath, $extension) {
		$source_info = $this->getFileInfo($sourcepath);
		$targetpath = $source_info['dirname'];

		switch ($extension) {
			case 'odt':
				$convertTo = 'odt:writer8';
				break;
			case 'doc':
				$convertTo = 'doc:"MS Word 97"';
				break;
			default:
			$this->_status = "Can not convert '$sourcepath' to $extension";
			throw new Exception($this->_status,E_USER_ERROR);
		}

		$in = escapeshellarg($sourcepath);
		$out = escapeshellarg($targetpath);

		/* Création de répertoire temporaire pour le profile */
		$temp_profile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('OTX');
		mkdir($temp_profile, 0755, true);

		$command = "{$this->_config->soffice->officepath} --norestore --headless -env:UserInstallation=file://{$temp_profile} --convert-to {$convertTo} --outdir {$out} {$in}";

		$returnvar = 0;
		$result    = '';

		$output = exec($command, $result, $returnvar);
		/* Suppression du profile temporaire */
		$this->rmdir($temp_profile);

		if ($returnvar) {
			@copy($sourcepath, $sourcepath.".error");
			@unlink($sourcepath);
			$this->_status = "error soffice";
			error_log("$command returned " . var_export($returnvar,true));
			throw new Exception($this->_status,E_USER_ERROR);
		}

		return $targetpath . DIRECTORY_SEPARATOR . $source_info['filename'] . "." . $extension;
	}

	private function rmdir( $path ) {
		$files = glob( $path . DIRECTORY_SEPARATOR . '*', GLOB_MARK );
		foreach( $files as $file ){
			if( is_file($file) )
				unlink( $file );
			else
				$this->rmdir( $file );
		}
		rmdir($path);
	}

	protected function getFileInfo($path) {
		$infos = pathinfo($path);
		$infos['realpath'] = realpath($path);
		return $infos;
	}

	private function getmime($sourcepath) {
		$mime = mime_content_type($sourcepath);
		return $mime;
	}

	protected function cleanup(){
		$this->rmdir($this->doc_tmp['dirname']);
	}

	protected function _orphanNotes($extended = false, ZipArchive $za) {
		$this->_db->query("DELETE FROM NoteTexte where idDocument=".$this->iddocument);
		$this->_db->query("DELETE FROM NotePossible where idDocument=".$this->iddocument);

		$orphan = new OrphanNotesParser($this->_db, $this->iddocument);
		$orphan->XMLLaunchParseOne($this->xml);

		if($extended) {
			$profondeur = 10;
			$precision_date = 2;
		} else {
			$profondeur = 1; // 1 pour une détection rapide, 5 ou 10 pour une détection plus approfondie
			$precision_date = 4; //4 ou moins, 4 si on ne veut considérer que les dates à quatre chiffre suivies d'une note
		}

		$orphan ->XMLLaunchParseTwo($this->xml, $profondeur, $precision_date)
			->phaseFinale();

		$za->addFromString('final.xml', $orphan->XMLText);
		$za->close();

		$this->output['orphannotes'] = array(
			'nbnotestrouvees'   => $orphan->nbNotesTrouvees,
			'nbpropositions'    => $orphan->nbPropositions,
			'props'             => $orphan->props,
			'nberreurs'         => $orphan->nbErreurs,
			'erreurs'           => $orphan->erreurs,
			'nbasterisques'     => $orphan->nbAsterisques,
			'nbromains'         => $orphan->nbRomains,
			'texteaffichage'    => $orphan->getTexte(),
			'boutondetection'   => $extended,
			'errorsappels'      => $orphan->getErrorsAppels(),
			'realficname'       => $this->doc_source['basename'],
			'iddocument'        => $this->iddocument
		);

		$this->output['orphannotes']['nberrorsappels'] = count($this->output['orphannotes']['errorsappels']);
    }

	protected function _orphanNotesRecomposeDocument(ZipArchive $za) {
		$nbNotesTrouvees = $this->_db->query("SELECT COUNT(*) FROM NoteTexte WHERE idDocument=".$this->iddocument)->fetch()[0];

		$this->xml = $za->getFromName('final.xml');
		$za->deleteName('final.xml');
		$za->deleteName('content.xml');

		$modeleNote = '<text:note text:id="ftn%d" text:note-class="footnote"><text:note-citation>%d</text:note-citation><text:note-body><text:p text:style-name="Footnote">%s</text:p></text:note-body></text:note>';

		//Stylage des appels et notes
		for( $curnote = 1 ; $curnote <= $nbNotesTrouvees ; $curnote ++ ) {

			$motNote = substr($this->request["notenum".$curnote],strpos($this->request["notenum".$curnote],"@")+1);

			$texteNote = $this->_db->query("SELECT texteNote FROM NoteTexte WHERE idDocument=".$this->iddocument." AND numNote=".$curnote)->fetch()[0];

			//Au cas ou la note soit entre parenthese on enleve ces parenthèses
			if(preg_match("/^(.)*\([0-9]+\)[.]{0,3}$/i",trim($motNote))) {
				$motNote = str_replace("(".$curnote.")", sprintf($modeleNote, $curnote, $curnote, $texteNote), $motNote);
			} else {
				//Gestion du cas ou on a un mot du style 18707 (utilisation de substr_replace
				preg_match_all("/".$curnote."/",$motNote,$matches,PREG_OFFSET_CAPTURE);
				$tab = array_reverse($matches[0]);
				$motNote = substr_replace($motNote, sprintf($modeleNote, $curnote, $curnote, $texteNote), $tab[0][1], strlen($curnote));
			}

			if(trim($this->request["notenum".$curnote])!="")
				$this->xml = str_replace("@NOTE@".$this->request["notenum".$curnote], $motNote, $this->xml );
		}

		//On enleve tous les @NOTE@<chiffre> qui ne sont pas des notes
		$this->xml = preg_replace("/@NOTE@[0-9]*@/","",$this->xml);

		$za->addFromString('content.xml', $this->xml);
		$za->close();


		$ext = strtolower(pathinfo($this->doc_source['basename'], PATHINFO_EXTENSION));
		if ($ext !== 'odt') {
			$final_file = $this->convertDocument($this->doc_tmp['realpath'], $ext);
		} else {
			$final_file = $this->doc_tmp['realpath'];
		}

		$this->output['orphannotes'] = array('document' => file_get_contents($final_file), 'name' => $this->doc_source['basename']);
		// récupération terminée, on efface nos traces
		$this->_db->query("DELETE FROM NoteTexte where idDocument=".$this->iddocument);
		$this->_db->query("DELETE FROM NotePossible where idDocument=".$this->iddocument);
		unlink($final_file);
		$this->cleanup();
	}

}

class OrphanNotesParser {
    // Définition des types de passe
    const CHERCHE_NOTES = 0;
    const ACQUISITION_NOTES = 1;
    const CREATION_NOTES = 2;
    const FIN_DE_TRAITEMENT = 3;

    // Variable qui contient le texte de la note courrante
    protected $_lectureNoteCourrante = "";
    // Variable qui contient le numéro de la note courrante
    protected $_numeroNoteCourrante = 0;
    protected $_nombreNotes = 0;
    protected $_nombreNotesPossibles = 0;

    //Ajout par jean
    protected $_pointeurNote = 0;
    //Stockage des erreurs et propositions rencontrées
    protected $_props = array();
    protected $_erreurs = array();
    protected $_idDocument = 0;

    // Variable tampon d'écriture dans le flux XML
    protected $_toWrite = "";
    protected $_nbErreurs = 0;
    protected $_nbAsterisques = 0;
    protected $_nbRomains = 0;
    protected $_nbPropositions = 0;
    protected $_nbNotesTrouvees = 0;
    protected $_txtAsterisques = array();
    protected $_txtRomains = array();
    protected $_toView = "";

    // Flux XML
    protected $_XMLText = "";
    protected $_data_text_prec ="";
    protected $_prof = 1;
    protected $_prec_date = 2;
    //Flux pour la lecture du doc
    protected $_texteToAffiche ="";

    protected $_passe = 0;

    /**
     * Constructeur
     * Attend une instance PDO
     *
     * @param mixed $db une instance PDO
     * @access public
     */
    public function __construct($db, $idDocument) {
        $this->_db = $db;
        $this->_idDocument = (int) $idDocument;
    }

    /**
     * Getter
     *
     * @param string $name nom de la variable à récupérer
     * @access public
     */
    public function __get($name) {
        return isset($this->{'_'.$name}) ? $this->{'_'.$name} : null;
    }

    /**
     * Fonction associée à l’événement début d’élément
     *
     * @param parser pointeur sur le parser XML déclaré
     * @param name nom de la balise qui a déclenché l'évènement
     * @param attrs attributs éventuels
     * @author Jean Lamy, Cédric Rosa
     * @access protected
     * Dernière modification : 24-08-2004
     */
    protected function _startElementHandler($parser, $name, $attrs) {
        //Si on est en phase de création des appels de notes
        if ($this->_passe == self::CREATION_NOTES) {
            // On écrit le nom de la balise qu'on vient de créer
            $this->_toWrite .= "<".strtolower($name);
            $this->_toView .="<".strtolower($name);
            while (list ($key, $val) = each ($attrs))
            {
                $this->_toWrite .= " ".strtolower($key)."="."\"".$this->_SymbolsToCodedSymbols($val)."\"";
                $this->_toView .= " ".strtolower($key)."="."\"".$this->_SymbolsToCodedSymbols($val)."\"";
            }
            $this->_toWrite .= ">";
            $this->_toView .= ">";
        } elseif ($this->_passe == self::ACQUISITION_NOTES) { // on est en acquisition du texte des notes
            if($name == "TEXT:P") {
                $this->_numeroNoteCourrante++; // Normalement c'est une nouvelle note
            } elseif( $name == "TEXT:SPAN") { // si il y a des italiques, il faut les garder dans la note courrante
                $this->_lectureNoteCourrante.= "<".strtolower($name);
                while (list ($key, $val) = each ($attrs))
                    $this->_lectureNoteCourrante .= " ".strtolower($key)."="."\"".$this->_SymbolsToCodedSymbols($val)."\"";
                $this->_lectureNoteCourrante.=">";
            }
        }
    }

    /**
     * Fonction associée à l’événement fin d’élément
     *
     * @param parser pointeur sur le parser XML déclaré
     * @param name nom de la balise qui a déclenché l'évènement
     * @author Jean Lamy, Cédric Rosa
     * @access protected
     * Dernière modification : 24-08-2004
     */
    protected function _endElementHandler($parser, $name) {
        $txtNote ="";
        if ($this->_passe == self::ACQUISITION_NOTES && $this->_lectureNoteCourrante != "") {
            if($name == "TEXT:SPAN") { //si fermeture d'une balise de mise en forme on l'écrit
                $this->_lectureNoteCourrante .= "</".strtolower($name).">";
            } elseif($name == "TEXT:P") {
                //On est en acquisition des notes, on recherche si le texte présent contient une note potentielle...
                //Si le texte commence par une balise, on l'enleve pour après traiter si c'est une note
                if(preg_match( "/^<text:span/i" , trim( $this->_lectureNoteCourrante ) ) ) { // note en italique, gras, exposant,...
                    $txtNote = substr($this->_lectureNoteCourrante,strpos($this->_lectureNoteCourrante,">")+1,strlen($this->_lectureNoteCourrante));
                    $debutbalise = substr($this->_lectureNoteCourrante,0,strpos($this->_lectureNoteCourrante,">")+1); // on stocke la balise de debut
                } else
                    $txtNote = $this->_lectureNoteCourrante ; // On met txtNote a lectureNoteCourrante

                if( preg_match( "/^[[(]?[0-9]+[])]?/i" , $txtNote , $regs ) ) { // si notre txtNote commence par une note
                    $noteTrouvee = preg_replace( "/[^0-9]/" , "" , $regs[0] ); // note trouvee
                    if ( (int)$noteTrouvee == (int)$this->_numeroNoteCourrante ) { // si cela correspond au num courant de la note qu'on cherche
                        //il faut maintenant retirer la note trouvée de son texte.
                        $txtNote = preg_replace("/^[[(]?[0-9]+[])]?/","",$txtNote);
                        if($txtNote[0] == ".")
                            $txtNote = substr($txtNote,1,strlen($txtNote));
                        if(!empty($debutbalise))
                            $txtNote = $debutbalise . $txtNote;
                        //On ajoute la note possible dans la base
                        $this->_db->query("INSERT into NoteTexte (numNote, texteNote, idDocument) VALUES (" . $this->_numeroNoteCourrante . " ," . $this->_db->quote( $txtNote ) . " , " . $this->_idDocument . ")");
                        // ... et on passe à la lecture de la suivante en remettant lectureNoteCourrante à vide
                        $this->_lectureNoteCourrante = "";
                    } else { //sinon on affiche une erreur
                        $this->_erreurs[] = "<strong>Erreur !</strong> La note ".$noteTrouvee." a été trouvée alors que la note ".$this->_numeroNoteCourrante." était attendue<br />";
                        // On essaie quand même de continuer
                        $this->_lectureNoteCourrante = "";
                        $this->_nbErreurs ++;
                    }
                } else { // sinon si ce n'est apparemment pas une note
                    if(preg_match("/^[*]/",$txtNote)) { // si c'est une astérisque on en tient compte (et on teste avec txtNote qui ne contient plus l'éventuelle balise de début
                        $this->_nbAsterisques++;
                        $this->_txtAsterisques[] = $this->_lectureNoteCourrante;
                        $this->_erreurs[] =  "<strong> Attention !</strong>Une note en astérisque a été trouvée. Elle ne sera pas prise en compte dans le processus de reconnaissance des notes de bas de page.<br />";
                        $this->_lectureNoteCourrante = "";
                        $this->_numeroNoteCourrante--;
                    } elseif(preg_match("/^(I|X|V|D|M|L|C)+./",$txtNote)) { //detection d'une note en chiffre romain
                        $this->_nbRomains++;
                        $this->_txtRomains[] = $this->_lectureNoteCourrante;
                        $this->_erreurs[] =  "<strong> Attention !</strong>Une note en chiffre romain a été trouvée. Elle ne sera pas prise en compte dans le processus de reconnaissance des notes de bas de page.<br />";
                        $this->_lectureNoteCourrante = "";
                        $this->_numeroNoteCourrante--;
                    } else { //sinon si c'est un texte sans note ni asterisque
                        if($this->_numeroNoteCourrante!=1 || $this->_numeroNoteCourrante!=$this->_nbAsterisques) { // si on est pas au début des notes, on raccroche le texte a la note précédente
                            $this->_erreurs[] =  "<strong>Attention !</strong>Une note sans numéro a été trouvée, elle est rattachée à la précédente.<br />";
                            $this->_numeroNoteCourrante--;
                            $row =  $this->_db->query("SELECT * from NoteTexte where numNote={$this->_numeroNoteCourrante} AND idDocument={$this->_idDocument}")->fetch();
                            $this->_lectureNoteCourrante =$row["texteNote"]."</text:p><text:p text:style-name=\"Footnote\">".$this->_lectureNoteCourrante;
                            //echo "lectureNote : ".$this->_lectureNoteCourrante."<br />";
                            $this->_db->query("UPDATE NoteTexte SET texteNote=".  $this->_db->quote($this->_lectureNoteCourrante) ." where numNote={$this->_numeroNoteCourrante} AND idDocument={$this->_idDocument}");
                            $this->_lectureNoteCourrante = "";
                        } else { // sinon on affiche une erreur
                            $this->_erreurs[] = "<strong>Erreur !</strong>Une note n'a pas pu être identifiée<br /><strong>Texte de la note :</strong> " . $this->_lectureNoteCourrante. "<br />";
                            $this->_lectureNoteCourrante = "";
                            $this->_nbErreurs++;
                        }
                    }
                }
            }
        } elseif ($this->_passe == self::CREATION_NOTES) {
            if(strlen ($this->_toWrite)>0 && $this->_toWrite[strlen ($this->_toWrite)-1] == ">") {
                $this->_toWrite = rtrim($this->_toWrite, ">")."/>";
                $this->_toView = rtrim($this->_toWrite, ">")."/>";
            } else {
                $this->_toWrite .= "</".strtolower($name).">";
                $this->_toView .= "</".strtolower($name).">";
            }
            // Fin de balise, on peut écrire dans le fichier texte
            $this->_XMLText .= $this->_toWrite;
            $this->_texteToAffiche .= $this->_toView;
            $this->_toWrite = "";
            $this->_toView = "";
        }
    }

    /** Fonction associée à l’événement texte (détection de texte)
     * @param parser pointeur sur le parser XML déclaré
     * @param data_text le texte lu
     * @author Jean Lamy, Cédric Rosa
     * Dernière modification : 24-08-2004
     * @access protected
     */
    protected function _texteHandler ($parser, $data_text) {
        $probaNote = 1;

        $data_text = $this->_SymbolsToCodedSymbols($data_text);
        if ($this->_passe == self::CHERCHE_NOTES) {
            //Recherche  de la balise #Notes# ou #Notes (insensible a la casse)
            if ( stristr($data_text,"#Notes#") || stristr($data_text,"#Notes") )
                $this->_passe = self::ACQUISITION_NOTES; // on passe alors en acquisition des notes
        }
        // Phase d'acquisition des notes, on les met dans le tableau prévu pour
        elseif ($this->_passe == self::ACQUISITION_NOTES ) {
            $this->_lectureNoteCourrante .= $data_text;
        } elseif ($this->_passe == self::CREATION_NOTES) {
            $mots = explode (" ", $data_text);
            $phrase = "";
            for ($i=0; $i< count($mots); $i++) {
                $mot = $mots[$i];
                $str = utf8_decode($mot);
                // On sauvegarde $str
                $str2 = $str;
                //DETECTION DES APPELS POTENTIELS
                //DIFFERENTS CAS POSSIBLES et différent suivant si on est en passe rapide ou approfondie
                // (paramètre prof)
                //si la note contient un mot suivit d'un chiffre et éventuellement dun signe de ponctuation
                if(preg_match("/[()]?[0-9]+[-]?[0-9]+[()]+[0-9]+[[:punct:]]?/",$str)) { // note de la forme 67)4
                    if(preg_match("/[[:punct:]]$/i",$str))
                        $probaNote = "0.75";
                    $str = preg_replace("/^[()]?[0-9]+[-]?[0-9]+[()]+/","",$str);
                    $str = preg_replace("/[^0-9]/","",$str);
                } else if(preg_match("/^[^0-9]+[0-9]+[[:punct:]]{0,5}$/i",$str)) {
                    if(preg_match("/[[:punct:]]$/i",$str))
                        $probaNote = "0.75";
                    //on enleve tout ce qui n'est pas un chiffre
                    $str = preg_replace("/[^0-9]/","",$str);
                } else if( preg_match("/[0-9]{4}[0-9]+[[:punct:]]?/i",$str)) { //gestion date à quatre chiffres
                    //on enleve les 4 premier chiffres
                    $str = substr($str,4,strlen($str));
                    $str = preg_replace("/[^0-9]/","",$str);
                } else if( preg_match("/[0-9]{3}[0-9]+[[:punct:]]?/i",$str) && $this->_prec_date < 4) { //gestion date à trois chiffres
                    //on enleve les 3 premier chiffres
                    $probaNote = "0.50";
                    $str = substr($str,3,strlen($str));
                    $str = preg_replace("/[^0-9]/","",$str);
                } else if( preg_match("/[0-9]{2}[0-9]+[[:punct:]]?/i",$str) && $this->_prec_date < 4) { //gestion date à deux chiffres
                    //on enleve les 2 premier chiffres
                    $probaNote = "0.25";
                    $str = substr($str,2,strlen($str));
                    $str = preg_replace("/[^0-9]/","",$str);
                } else if(preg_match("/[0-9][[:punct:]]?/",$str)) {
                    $probaNote = "0.75";
                    $str = preg_replace("/[^0-9]/","",$str);
                }

                //construction de l'extrait
                $mot_marquee = str_replace($str,"<sup>".$str."</sup>",$mot); // pour l'affichage de l'extrait
                if($this->_data_text_prec) {
                    $mots_prec = explode(" ",$this->_data_text_prec);
                }
                $len = count($mots_prec);

                if($i < 1)
                    $extraitNote = @$mots_prec[$len-2]." ".@$mots_prec[$len-1]." ".@$mots_prec[$len]."<strong>".$mot_marquee."</strong> ".@$mots[$i+1]." ".@$mots[$i+21]." ".@$mots[$i+3];
                elseif($i < 2)
                    $extraitNote = @$mots_prec[$len-1]." ".@$mots_prec[$len]." ".@$mots[$i-1]." <strong>".$mot_marquee."</strong> ".@$mots[$i+1]." ".@$mots[$i+21];
                elseif($i < 3)
                    $extraitNote = @$mots[$i-2]." ".@$mots[$i-1]." <strong>".@$mot_marquee."</strong> ".@$mots[$i+1];
                else
                    $extraitNote = @$mots[$i-3]." ".@$mots[$i-2]." ".@$mots[$i-1]." <strong>".$mot_marquee."</strong> ".@$mots[$i+1];

                $extraitNote = "...".$extraitNote."...";

                if(!$this->_prof)
                    $this->_prof = 1;

                if ((int)$str != 0 && (int)$str <= $this->_nombreNotes && abs(((int)$str -  $this->_pointeurNote)) <= (int)$this->_prof) {
                    $this->_db->query("INSERT into NotePossible (idNote,numNote,motNote,extraitMotNote,probaNote,idDocument) VALUES (null,".$str.", \"".($this->_nombreNotesPossibles+1)."@".$mot."\",\"".$extraitNote."\", '$probaNote',".$this->_idDocument.")");

                    $this->_numeroNoteCourrante = $str;
                    if((int)$str - (int)$this->_pointeurNote >= 0 )
                        $this->_pointeurNote++;

                    $this->_nombreNotesPossibles++;
                    $this->_toWrite .= "@NOTE@".$this->_nombreNotesPossibles."@".$this->_SymbolsToCodedSymbols($mot);
                    $this->_toView .= "<notepossible>".$this->_SymbolsToCodedSymbols($mot)."</notepossible>";
                } else { // si ce n'est pas une note possible
                    $this->_toWrite .= $mot;
                    $this->_toView .= $mot;
                }

                if ($i != count ($mots)-1) {
                    $this->_toWrite .= " ";
                    $this->_toView .= " ";
                }
            }

            if ( stristr($data_text,"#Notes#") || stristr($data_text,"#Notes") ) {
                if(count($this->_txtAsterisques)!=0) {
                    $this->_XMLText .= "<text:p text:style-name=\"Normal\">#Notes Asterisques#</text:p>";
                    for($elem = 0 ; $elem < count($this->_txtAsterisques) ; $elem ++)
                        $this->_XMLText .= "<text:p text:style-name=\"Normal\">". $this->_txtAsterisques[$elem]."</text:p>";
                }
                if(count($this->_txtRomains)!=0) {
                    $this->_XMLText .= "<text:p text:style-name=\"Normal\">#Notes Romains#</text:p>";
                    for($elem = 0 ; $elem < count($this->_txtRomains) ; $elem ++)
                        $this->_XMLText .= "<text:p text:style-name=\"Normal\">". $this->_txtRomains[$elem]."</text:p>";
                }
                $this->_XMLText .= "</office:text></office:body></office:document-content>";
                $this->_texteToAffiche .= "</office:text></office:body></office:document-content>";
                $this->_passe = self::FIN_DE_TRAITEMENT;
                // Le traitement est fini, on va écrire le $this->_toWrite puis on stoppe
            }
        }

        $this->_data_text_prec = $data_text;
    }

    /** Fonction associée à un événement qui n'est pas pris en compte par les autres Handler */
    protected function _defaultHandler ($parser, $data) {
        // Si on est dans une phase de création, il faut recopier ce qu'on lit dans le fichier destination
        if ($this->_passe == self::CREATION_NOTES)
            $this->_XMLText .= $data;
    }

    /**
     * Lancement de la phase 1 : acquisition du texte des notes
     *
     * @param data le texte XML
     * @author Cédric Rosa
     * @access public
     * Dernière modification : 01-06-2004
     */
    public function XMLLaunchParseOne($data) {
        //On commence par chercher où sont situées les notes
        $this->_passe = self::CHERCHE_NOTES;
        // Déclaration du parser xml et des fonctions associés
        $xml_parser = xml_parser_create("UTF-8");
        xml_set_element_handler($xml_parser, array($this, "_startElementHandler"), array($this, "_endElementHandler"));
        xml_set_character_data_handler($xml_parser, array($this, "_texteHandler"));
        if (!xml_parse($xml_parser, $data)) {
            die(sprintf("Erreur XML: %s à la ligne %d, colonne %d ", xml_error_string(xml_get_error_code($xml_parser)), xml_get_current_line_number($xml_parser),xml_get_current_column_number($xml_parser)));
        }
        // On libère la mémoire affectée au parser XML
        xml_parser_free($xml_parser);

        return $this;
    }

    /**
     * Lancement de la phase 2 : recherche des appels de notes
     *
     * @param data le texte XML
     * @author Cédric Rosa
     * @access public
     * Dernière modification : 01-06-2004
     */
    public function XMLLaunchParseTwo($data, $profondeur, $precision_date) {
        $this->_prec_date = $precision_date;
        $this->_prof = $profondeur;
        $this->_passe = self::CREATION_NOTES;
        $this->_nombreNotes = $this->_numeroNoteCourrante;
        $this->_numeroNoteCourrante = 1;

        // Déclaration du parser xml et des fonctions associés
        $xml_parser = xml_parser_create("UTF-8");
        xml_set_element_handler($xml_parser, array($this, "_startElementHandler"), array($this, "_endElementHandler"));
        xml_set_character_data_handler($xml_parser, array($this, "_texteHandler"));
        xml_set_default_handler($xml_parser, array($this, "_defaultHandler"));

        if (!xml_parse($xml_parser, $data))
            die(sprintf("Erreur XML: %s à la ligne %d", xml_error_string(xml_get_error_code($xml_parser)), xml_get_current_line_number($xml_parser)));
        // On libère la mémoire affectée au parser XML
        xml_parser_free($xml_parser);

        return $this;
    }

    /**
     * Lancement de la phase 3 (finale) : construction du tableau regroupant les différentes propositions d'appels de notes
     *
     * @author Cédric Rosa, Jean Lamy
     * @access public
     * Dernière modification : 24-08-2004
     */
    public function phaseFinale() {
        $this->_nbNotesTrouvees = $this->_db->query("SELECT COUNT(*) FROM NoteTexte WHERE idDocument=".$this->_idDocument)->fetch()[0];
        $this->_nbPropositions = $this->_db->query('SELECT COUNT(*) FROM NotePossible WHERE idDocument ='.$this->_idDocument)->fetch()[0];

        for ($i = 1; $i <= $this->_nbNotesTrouvees; $i++) {
            $rows = $this->_db->query("SELECT * FROM NotePossible WHERE numNote =".$i." AND idDocument =".$this->_idDocument." ORDER BY probaNote DESC, idNote ASC");

            $count = $rows->rowCount();

            $lesprops = array();
            if ( $count > 1 ) {
                $first = TRUE;
                $uneprop = array();
                foreach ($rows as $row) {
                    if ($first) {
                        $uneprop['name'] = "notenum".$i;
                        $uneprop['value'] = $row["motNote"];
                        $uneprop['txt'] = $row["extraitMotNote"];
                        $uneprop['checked'] = "checked";
                        $first = FALSE;
                    } else {
                        $uneprop['name'] = "notenum".$i;
                        $uneprop['value'] = $row["motNote"];
                        $uneprop['txt'] = $row["extraitMotNote"];
                        $uneprop['checked'] = "";
                    }
                    $lesprops[] = $uneprop;
                }
                $this->_props[$i] = $lesprops;
            } elseif( $count == 1) {
                $row = $rows->fetch();
                $uneprop['name'] = "notenum".$i;
                $uneprop['value'] = $row["motNote"];
                $uneprop['txt'] = $row["extraitMotNote"];
                $uneprop['checked'] = "checked";
                $lesprops[] = $uneprop;
                $this->_props[$i] = $lesprops;
            }

        }//for

        return $this;
    }

    /**
     * Fonction pour la gestion des caracètres spéciaux XML
     * @param $val le caractère à transformer
     * @return le caractère bien transformé si nécessaire
     * @author Cédric Rosa
     * @access protected
     * Dernière modification : 01-06-2004
     */
    protected function _SymbolsToCodedSymbols($val) {
        return strtr($val, array(
            '&'  => '&amp;',
            '<'  => '&lt;',
            '>'  => '&gt;',
            '\'' => '&apos;',
            '"'  => '&quot;',
        ));
    }

    /**
     * Retourne le texte à afficher après nettoyage
     *
     * @param string $texte le texte à nettoyer
     * @access public
     */
    public function getTexte() {
        //enlever d'abord ce qui est avant office:body
        $posdebut= strpos($this->_texteToAffiche,"<office:body>");
        $posend=strpos($this->_texteToAffiche,"</office:body>");
        $this->_texteToAffiche = substr($this->_texteToAffiche,$posdebut,$posend);

        // nettoyage
        $this->_texteToAffiche = preg_replace("/<text:p ([^>]+)>/","<p style=\"font-size : 11px; font-family : Verdana ; text-align : justify\">",$this->_texteToAffiche);
        $this->_texteToAffiche = preg_replace("/<\/text:p>/","</p>\n",$this->_texteToAffiche);
        $this->_texteToAffiche = preg_replace("/<draw:image ([^>]+)>/","",$this->_texteToAffiche);
        $this->_texteToAffiche = preg_replace("/<\/draw:image>/","",$this->_texteToAffiche);
        //on tranforme text:p en p
        $arr = preg_split("/<\/p>/",$this->_texteToAffiche,-1,PREG_SPLIT_NO_EMPTY);
        $count=count($arr);
        $nnotes=0;
        $texte_modifie="";
        for($i=1; $i<$count; $i++) {
            if(preg_match("/notepossible/",$arr[$i])) {
                $texte_modifie .= $arr[$i]."</p>";
            }
            else $texte_modifie .= "";
        }

        $texte_modifie = preg_replace("/<notepossible>/","<span style=\"font-weight : bold ; background-color :#FCFF00 ; color : #333333\">",$texte_modifie);
        $texte_modifie = preg_replace("/<\/notepossible>/","</span>",$texte_modifie);

        return $texte_modifie;
    }

    /**
     * Retourne un tableau des notes non trouvées
     *
     * @access public
     * @return array les notes non trouvées
     */
    public function getErrorsAppels() {
        $errorsAppels = array();
        for( $i=1; $i <= $this->_nbNotesTrouvees ; $i++) {
            if(empty($this->_props[$i])) {
                $errorsAppels[] = "Appel de note $i non trouvé";
            }
        }

        return $errorsAppels;
    }
}

