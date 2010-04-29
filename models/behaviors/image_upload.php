<?php
/**
 * ImageUpload Behavior
 *
 * This behaviour extends MeioUpload Behaviour (http://github.com/jrbasso/MeioUpload). 
 * It adds two more options. See the comments below.
 *
 * @author Vadim Frolov (fralik@gmail.com)
 * @package app
 * @subpackage app.models.behaviors
 * @filesource http://github.com/fralik/CakePHP_ImageMultiselect
 * @version 1.0
 * @lastmodified 2010-24-03
 */
App::import('Core', array('File', 'Folder'));
App::import('Behavior', 'MeioUpload.MeioUpload');

class ImageUploadBehavior extends MeioUploadBehavior {
/**
 * The default options for the behavior
 */
    var $image_default_options = array(
        'generateName' => true, // Wether to generate random file name or use original uploaded name
        'maxFiles' => 500, // maximum number of files in a directory. If 0 then nothing happens, if > 0
                         // then we start to create directories /$options['dir']/<i>, with maxFiles in every
                         // directory <i>
		'fields' => array(
			'dir' => 'dir',
			'filesize' => 'filesize',
			'mimetype' => 'mimetype',
            'preview_link' => 'preview_link',
            'name' => 'name' // human-readable name that could be used to reference this image
		),
    );
    /* Note that there are additional thumbnails parameters: maxDimension -> a,
     * heightPortrait, widthLandscape. If maxDimension is set to 'a' (adjustable),
     * then the thumbnail is created based on the orientation. heightPortrait and
     * widthLandscape define maximal size of the sides respectively.
     */
    
/**
 * Setup the behavior. It stores a reference to the model, merges the default options with the options for each field, and setup the validation rules.
 * 
 * Note from V. Frolov: I overrided setup, because I do not want to create directories for thumbnails right away.
 *
 * @param $model Object
 * @param $settings Array[optional]
 * @return null
 * @author Vinicius Mendes
 */
	function setup(&$model, $settings = array()) {        
        // merge original options with our custom set
        $this->defaultOptions = $this->_arrayMerge($this->defaultOptions, $this->image_default_options);
        
		$this->__fields[$model->alias] = array();
		foreach ($settings as $field => $options) {
			// Check if they even PASSED IN parameters
			if (!is_array($options)) {
				// You jerks!
				$field = $options;
				$options = array();
			}

			// Inherit model's lack of table use if not set in options
			// regardless of whether or not we set the table option
			if (!$model->useTable) {
				$options['useTable'] = false;
			}

			// Merge given options with defaults
			$options = $this->_arrayMerge($this->defaultOptions, $options);

			// Check if given field exists
			if ($options['useTable'] && !$model->hasField($field)) {
				trigger_error(sprintf(__d('meio_upload', 'MeioUploadBehavior Error: The field "%s" doesn\'t exists in the model "%s".', true), $field, $model->alias), E_USER_WARNING);
			}

			// Including the default name to the replacements
			if ($options['default']) {
				if (strpos($options['default'], '.') !== false) {
					trigger_error(__d('meio_upload', 'MeioUploadBehavior Error: The default option must be the filename with extension.', true), E_USER_ERROR);
				}
				$this->_includeDefaultReplacement($options['default']);
			}

			// Verifies if the thumbsizes names is alphanumeric
			foreach ($options['thumbsizes'] as $name => $size) {
				if (empty($name) || !ctype_alnum($name)) {
					trigger_error(__d('meio_upload', 'MeioUploadBehavior Error: The thumbsizes names must be alphanumeric.', true), E_USER_ERROR);
				}
			}

			// Process the max_size if it is not numeric
			$options['maxSize'] = $this->_sizeToBytes($options['maxSize']);

			// Replace tokens of the dir and field, check it doesn't have a DS on the end
			$tokens = array('{ModelName}', '{fieldName}', '{DS}', '/', '\\');
			$options['dir'] = rtrim($this->_replaceTokens($model, $options['dir'], $field, $tokens), DS);
			$options['uploadName'] = rtrim($this->_replaceTokens($model, $options['uploadName'], $field, $tokens), DS);

			// Create the folders for the uploads
			//$this->_createFolders($options['dir'], array_keys($options['thumbsizes']));

			// Replace tokens in the fields names
			if ($options['useTable']) {
				foreach ($options['fields'] as $fieldToken => $fieldName) {
					$options['fields'][$fieldToken] = $this->_replaceTokens($model, $fieldName, $field, $tokens);
				}
			}
			$this->__fields[$model->alias][$field] = $options;
		}
	}

/**
 * Deletes the files marked to be deleted in the delete method.
 * A file can be marked to be deleted if it is overwriten by
 * another or if the user mark it to be deleted. It is the same
 * as afterSave function and is neededto process Model->delete 
 * method correctly.
 *
 * @param $model Object
 * @author Vadim Frolov
 */
	function afterDelete(&$model) {
		foreach ($this->__filesToRemove as $file) {
			if (!empty($file['name'])) {
				$this->_deleteFiles($model, $file['field'], $file['name'], $file['dir']);
			}
		}
		// Reset the filesToRemove array
		$this->__filesToRemove = array();
	}

/**
 * Function to create Thumbnail images scaled based on the maximal side
 *
 * @author Jose Diaz-Gonzalez, Vadim Frolov
 * @param String source file name (without path)
 * @param String target file name (without path)
 * @param String path to source and destination (no trailing DS)
 * @param Array
 * @return void
 */
	function _createThumbnail(&$model, $source, $target, $fieldName, $params = array()) {
		$params = array_merge(
			array(
				'thumbWidth' => 150,
				'thumbHeight' => 225,
				'maxDimension' => '',
				'thumbnailQuality' => $this->__fields[$model->alias][$fieldName]['thumbnailQuality'],
				'zoomCrop' => false,
                'heightPortrait' => 600,
                'widthLandscape' => 600,
			),
			$params);

		// Import phpThumb class
		App::import('Vendor','phpthumb', array('file' => 'phpThumb'.DS.'phpthumb.class.php'));

		// Configuring thumbnail settings
		$phpThumb = new phpthumb;
		$phpThumb->setSourceFilename($source);

		if ($params['maxDimension'] == 'w') {
			$phpThumb->w = $params['thumbWidth'];
		} else if ($params['maxDimension'] == 'h') {
			$phpThumb->h = $params['thumbHeight'];
        } else if ($params['maxDimension'] == 'a') // adjustable
        {
            $phpThumb->hp = $params['heightPortrait'];
            $phpThumb->wl = $params['widthLandscape'];
		} else {
			$phpThumb->w = $params['thumbWidth'];
			$phpThumb->h = $params['thumbHeight'];
		}

		$phpThumb->setParameter('zc', $this->__fields[$model->alias][$fieldName]['zoomCrop']);
		if (isset($params['zoomCrop'])){
			$phpThumb->setParameter('zc', $params['zoomCrop']);
		}
		$phpThumb->q = $params['thumbnailQuality'];

		$imageArray = explode(".", $source);
		$phpThumb->config_output_format = $imageArray[1];
		unset($imageArray);

		$phpThumb->config_prefer_imagemagick = $this->__fields[$model->alias][$fieldName]['useImageMagick'];
		$phpThumb->config_imagemagick_path = $this->__fields[$model->alias][$fieldName]['imageMagickPath'];

		// Setting whether to die upon error
		$phpThumb->config_error_die_on_error = true;
		// Creating thumbnail
		if ($phpThumb->GenerateThumbnail()) {
			if (!$phpThumb->RenderToFile($target)) {
				$this->_addError('Could not render image to: '.$target);
			}
		}
	}
    
/**
 * Uploads the files
 *
 * @param $model Object
 * @param $data Array Optional Containing data to be saved
 * @return array
 * @author Vinicius Mendes
 */
	function _uploadFile(&$model, $data = null) {
		if (!isset($data) || !is_array($data)) {
			$data =& $model->data;
		}
		foreach ($this->__fields[$model->alias] as $fieldName => $options) {
            
			// Take care of removal flagged field
			// However, this seems to be kind of code duplicating, see line ~711
			if (!empty($data[$model->alias][$fieldName]['remove'])) {
				$this->_markForDeletion($model, $fieldName, $data, $options['default']);
				$data = $this->_unsetDataFields($model->alias, $fieldName, $data, $options);
				$result = array('return' => true, 'data' => $data);
				continue;
			}
			// If no file was selected we do not need to proceed
			if (empty($data[$model->alias][$fieldName]['name'])) {
				unset($data[$model->alias][$fieldName]);
				$result = array('return' => true, 'data' => $data);
				continue;
			}
			$pos = strrpos($data[$model->alias][$fieldName]['type'], '/');
			$sub = substr($data[$model->alias][$fieldName]['type'], $pos+1);
			list($filename, $ext) = $this->_splitFilenameAndExt($data[$model->alias][$fieldName]['name']);

			// Put in a subfolder if the user wishes it
			if (isset($options['folderAsField']) && !empty($options['folderAsField']) && is_string($options['folderAsField'])) {
				$options['dir'] = $options['dir'] . DS . $data[$model->alias][$options['folderAsField']];
				$this->__fields[$model->alias][$fieldName]['dir'] = $options['dir'];
			}

			// Check whether or not the behavior is in useTable mode
			if ($options['useTable'] == false) {
                $result = false;
				// $this->_includeDefaultReplacement($options['default']);
				// $this->_fixName($model, $fieldName, false);
				// $saveAs = $options['dir'] . DS . $data[$model->alias][$options['uploadName']] . '.' . $sub;

				// // Attempt to move uploaded file
				// $copyResults = $this->_copyFileFromTemp($data[$model->alias][$fieldName]['tmp_name'], $saveAs);
				// if ($copyResults !== true) {
					// $result = array('return' => false, 'reason' => 'validation', 'extra' => array('field' => $field, 'error' => $copyResults));
					// continue;
				// }

				// // If the file is an image, try to make the thumbnails
				// if ((count($options['thumbsizes']) > 0) && count($options['allowedExt']) > 0 && in_array($data[$model->alias][$fieldName]['type'], $this->_imageTypes)) {
					// $this->_createThumbnails($model, $data, $fieldName, $saveAs, $ext, $options);
				// }

				// $data = $this->_unsetDataFields($model->alias, $fieldName, $model->data, $options);
				// $result = array('return' => true, 'data' => $data);
				continue;
			} 
            else 
            {
				// if the file is marked to be deleted, use the default or set the field to null
				if (!empty($data[$model->alias][$fieldName]['remove'])) {
					if ($options['default']) {
						$data[$model->alias][$fieldName] = $options['default'];
					} else {
						$data[$model->alias][$fieldName] = null;
					}
					//if the record is already saved in the database, set the existing file to be removed after the save is sucessfull
					if (!empty($data[$model->alias][$model->primaryKey])) {
						$this->_setFileToRemove($model, $fieldName);
					}
				}

				// If no file has been upload, then unset the field to avoid overwriting existant file
				if (!isset($data[$model->alias][$fieldName]) || !is_array($data[$model->alias][$fieldName]) || empty($data[$model->alias][$fieldName]['name'])) {
					if (!empty($data[$model->alias][$model->primaryKey]) || !$options['default']) {
						unset($data[$model->alias][$fieldName]);
					} else {
						$data[$model->alias][$fieldName] = $options['default'];
					}
				}

				//if the record is already saved in the database, set the existing file to be removed after the save is sucessfull
				if (!empty($data[$model->alias][$model->primaryKey])) {
					$this->_setFileToRemove($model, $fieldName);
				}

                // find suitable directory
                if ($options['maxFiles'] > 0)
                {
                    $dir = $this->_getSuitableDir($options);
                    if ($dir == "")
                    {
                        $result = array('return' => false, 'reason' => 'Can not get suitable directory');
                        continue;
                    }
                    $options['dir'] = $dir;
                }
                $this->__fields[$model->alias][$fieldName]['dir'] = $dir;
                
                // Fix the filename, removing bad characters and avoiding from overwriting existing ones
                if ($options['default'] == true) 
                {
                    $this->_includeDefaultReplacement($options['default']);
                }
                
                if (!isset($data[$model->alias]['UserName']) || strlen($data[$model->alias]['UserName']) == 0)
                {
                    $data[$model->alias]['UserName'] = $data[$model->alias][$fieldName]['name'];
                }
                else
                {
                    // add extension so we get no error from Meio
                    $data[$model->alias]['UserName'] = $data[$model->alias]['UserName'] . '.png';
                }
                $this->_fixUserName($model, 'UserName');
                
                // generate file name or use user supplied
                if ($options['generateName'] == true) 
                {
                    $new_filename = md5(uniqid(rand(), true));
                    $data[$model->alias][$fieldName]['name'] = $new_filename . '.' . $ext;
                } else 
                {
                    $this->_fixName($model, $fieldName);
                }
                
				// Also save the original image as uploadName if that option is not empty
				if (isset($options['uploadName']) && !empty($options['uploadName'])) {
					$saveAs = $options['dir'] . DS . $data[$model->alias][$options['uploadName']].'.'.$ext;
				} else {
					$saveAs = $options['dir'] . DS . $data[$model->alias][$fieldName]['name'];
				}

				// Attempt to move uploaded file
				$copyResults = $this->_copyFileFromTemp($data[$model->alias][$fieldName]['tmp_name'], $saveAs);
				if ($copyResults !== true) {
					$result = array('return' => false, 'reason' => 'validation', 'extra' => array('field' => $field, 'error' => $copyResults));
					continue;
				}

				// If the file is an image, try to make the thumbnails
				if ((count($options['thumbsizes']) > 0) && count($options['allowedExt']) > 0 && in_array($data[$model->alias][$fieldName]['type'], $this->_imageTypes)) 
                {
					$this->_createThumbnails($model, $data, $fieldName, $saveAs, $ext, $options);
                    foreach ($options['thumbsizes'] as $key => $value) 
                    {
                        // Generate the name for the thumbnail
                        if (isset($options['uploadName']) && !empty($options['uploadName'])) {
                            $preview_link = $this->_getThumbnailName($saveAs, $options['dir'], $key, $data[$model->alias][$options['uploadName']], $ext);
                        } else {
                            $preview_link = $this->_getThumbnailName($saveAs, $options['dir'], $key, $data[$model->alias][$fieldName]['name']);
                        }
                        break;
                    }
				}

				// Update model data
				$data[$model->alias][$options['fields']['dir']] = $dir;
				$data[$model->alias][$options['fields']['mimetype']] = $data[$model->alias][$fieldName]['type'];
				$data[$model->alias][$options['fields']['filesize']] = $data[$model->alias][$fieldName]['size'];
                $data[$model->alias][$options['fields']['preview_link']] = $preview_link;
                $data[$model->alias][$options['fields']['name']] = $data[$model->alias]['UserName'];
				if (isset($options['uploadName']) && !empty($options['uploadName'])) {
					$data[$model->alias][$fieldName] = $data[$model->alias][$options['uploadName']].'.'.$ext;
				} else {
					$data[$model->alias][$fieldName] = $data[$model->alias][$fieldName]['name'];
				}
				$result = array('return' => true, 'data' => $data);
				continue;
			}
		}
        
		if (isset($result)) {
			return $result;
		} else {
			return true;
		}
	}

/**
 * We have a field in the database with name Name. This function removes bad 
 * characters and replaces reserved words from this field. It updates the
 * $model->data.
 *
 * @param $fieldName String
 * @return void
 * @author Vadim Frolov
 */
	function _fixUserName(&$model, $fieldName, $checkName = true) 
    {
		// updates the filename removing the keywords thumb and default name for the field.
		list ($filename, $ext) = $this->_splitFilenameAndExt($model->data[$model->alias][$fieldName]);
		$filename = str_replace($this->patterns, $this->replacements, $filename);
		$filename = Inflector::slug($filename);
		$i = 0;
		$newFilename = $filename;
		if ($checkName) 
        {
            while (1)
            {
                $res = $model->FindByName($newFilename);
                if (isset($res[$model->alias]) > 0)
                {
                    $newFilename = $filename . '-' . $i++;
                    pr($newFilename);
                }
                else
                {
                    break;
                }
                unset($res);
            }
		}
		$model->data[$model->alias][$fieldName] = $newFilename;
	}

/**
 * Set a file to be removed in afterSave() callback
 *
 * @param $fieldName String
 * @return void
 * @author Vinicius Mendes
 */
	function _setFileToRemove(&$model, $fieldName) 
    {
		$filename = $model->field($fieldName);
        $dirname = $model->field($this->__fields[$model->alias][$fieldName]['fields']['dir']);
        //pr($filename);
		if (!empty($filename) && $filename != $this->__fields[$model->alias][$fieldName]['default']) {
			$this->__filesToRemove[] = array(
				'field' => $fieldName,
				'dir' => $dirname,
				'name' => $filename
			);
            //pr($this->__fields[$model->alias][$fieldName]['thumbsizes']);
			foreach($this->__fields[$model->alias][$fieldName]['thumbsizes'] as $key => $sizes){
				if ($key === 'normal') {
					$subpath = '';
				} else {
					$subpath = DS . 'thumb' . DS . $key;
				}
				$this->__filesToRemove[] = array(
					'field' => $fieldName,
					'dir' => $dirname . $subpath,
					'name' => $filename
				);
   			}
		}

	}

/**    
 * Iterates through directories, starting from
 * $base_dir and returns the full path of the directory
 * if there are fewer than $options['maxFiles'] files in it.
 * If the directory does not exists, then this function creates it.
 * $base_dir should ends with "/"
 *
 * @param $options String
 * @return String
 * @author Vadim Frolov
 **/
    function _getSuitableDir($options)
    {
        $max_files = $options['maxFiles'];
        $base_dir = $options['dir'];
        $return_dir = "";
        
        if (!is_dir($base_dir))
        {
            return $return_dir;
        }
        
        $dirs = scandir($base_dir);
        $new_dir = true;
        $last_dir = 0;
        if (count($dirs) != 2)
        {
            foreach ($dirs as &$dir)
            {
                if (strstr($dir, ".") != FALSE || strstr($dir, 'thumb') != FALSE)
                    continue;
                $files = scandir($base_dir . DS . $dir);
                $last_dir = $dir;
                if (count($files) - 3 < $max_files)
                {
                    $new_dir = false;
                    break;
                }
            }
            unset($dir);
        }
        else
        {
            $last_dir = 0;
        }
        if ($new_dir)
        {
            $last_dir = $last_dir + 1;
            $return_dir = $base_dir . DS . $last_dir;
			$this->_createFolders($return_dir, array_keys($options['thumbsizes']));

        }
        else
        {
            $return_dir = $base_dir . DS . $last_dir;
        }
        
        return $return_dir;
    }
}

?>