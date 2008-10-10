<?php

define("IMAGE_BIAS_VERTICAL", 1);
define("IMAGE_BIAS_HORIZONTAL", 2);

class Fishy_Image {
    /**
     * Presets of extensions to use while converting extensions to default
     * PHP image types
     */
    protected static $extension_map = array(
        'gif'  => IMAGETYPE_GIF,
        'jpg'  => IMAGETYPE_JPEG,
        'jpeg' => IMAGETYPE_JPEG,
        'png'  => IMAGETYPE_PNG
    );
    
    protected $image;
    protected $width;
    protected $height;
    protected $bias;
    protected $aspect_x;
    protected $aspect_y;
    
    public function __construct($image) {
        $this->image = $this->load_image($image);
        $this->prepare_data($this->image, true);
    }
    
    /**
     * Create a copy of current image
     *
     * @return resource Resource of copy image
     */
    public function clone_image() {
        $image = imagecreatetruecolor($this->width, $this->height);
        imagecopy($image, $this->image, 0, 0, 0, 0, $this->width, $this->height);
        
        return $image;
    }
    
    /**
     * Resize image to new output size
     *
     * @param $new_width The new width of image (use 0 with a valid height to calcular proportional)
     * @param $new_height The new height of image (use 0 with a valid width to calcular proportional)
     * @param $mode The mode of resize, 0 to normal resize, 1 to resize with crop, 2 to resize by reduction (and fill the blank with bgcolor given in fourth parameter)
     * @param $bgcolor Background color to fill when using resize mode 2
     * @return void
     */
    public function resize($new_width, $new_height, $mode = 0, $bgcolor = '#000000') {
        if ($new_width == 0) {
            $new_width = $new_height * $this->aspect_y;
        }
        
        if ($new_height == 0) {
            $new_height = $new_width * $this->aspect_x;
        }
        
        $src_x = $src_y = $dst_x = $dst_y = 0;
        
        $src_width = $this->width;
        $src_height = $this->height;
        
        $dst_width = $new_width;
        $dst_height = $new_height;
        
        $resized = imagecreatetruecolor($new_width, $new_height);
        $new_info = $this->prepare_data($resized);
        
        if ($mode == 1) {
            if ($this->aspect_x > $new_info['aspect_x']) {
                $src_height = $this->width * ($new_info['aspect_x']);
                $src_y = $this->height / 2 - $src_height / 2;
            } else {
                $src_width = $this->height * ($new_info['aspect_y']);
                $src_x = $this->width / 2 - $src_width / 2;
            }
        } elseif ($mode == 2) {
            imagefill($resized, 0, 0, $this->color_from_hex($bgcolor, $resized));
            
            if ($this->aspect_x > $new_info['aspect_x']) {
                $dst_width = $dst_height * ($this->aspect_y);
                $dst_x = $new_width / 2 - $dst_width / 2;
            } else {
                $dst_height = $dst_width * ($this->aspect_x);
                $dst_y = $new_height / 2 - $dst_height / 2;
            }
        }
        
        imagecopyresampled($resized, $this->image, $dst_x, $dst_y, $src_x, $src_y, $dst_width, $dst_height, $src_width, $src_height);
        
        imagedestroy($this->image);
        
        $this->image = $resized;
        $this->prepare_data($this->image, true);
    }
    
    /**
     * Output image to browser
     *
     * @param $type Type of image, use PHP standart constants, see http://br2.php.net/manual/en/function.image-type-to-mime-type.php for a list of valid types (default IMAGETYPE_JPEG)
     * @param $include_headers Automatic include output headers for content-type (default true)
     * @return void
     */
    public function output($type = IMAGETYPE_JPEG, $include_headers = true) {
    	$data = $this->bdata($type);
    	
        if ($include_headers) {
            header('Content-Type: ' . image_type_to_mime_type($type));
            header('Content-Length: ' . strlen($data));
        }
        
        echo $data;
    }
    
    /**
     * Get binary data of image
     *
     * @param $type Type of image, use PHP standart constants, see http://br2.php.net/manual/en/function.image-type-to-mime-type.php for a list of valid types (default IMAGETYPE_JPEG)
     * @return string Binary data of image
     */
    public function bdata($type = IMAGETYPE_JPEG) {
        $fn = $this->output_function($type);
        
        if ($fn === false) {
            throw new Fishy_Image_Exception($this, "File type " . image_type_to_mime_type($info[2]) . " is not supported");
        }
        
        ob_start();
        $fn($this->image);
        $data = ob_get_clean();
        
        return $data;
    }
    
    /**
     * Save image into a file
     *
     * @param $path Path to save image
     * @param $type Type output image
     * @return void
     */
    public function save($path, $type = false) {
        //compute output type
        if ($type === false) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            
            if (array_key_exists($ext, self::$extension_map)) {
                $type = self::$extension_map[$ext];
            } else {
                $type = IMAGETYPE_JPEG;
            }
        }
        
        $fn = $this->output_function($type);
        
        if ($fn === false) {
            throw new Fishy_Image_Exception($this, "File type " . image_type_to_mime_type($type) . " is not supported");
        }
        
        $fn($this->image, $path);
    }
    
    /**
     * Free memory of current image
     *
     * @return void
     */
    public function destroy() {
        if ($this->image === null) {
            return;
        }
        
        imagedestroy($this->image);
        
        $this->image = null;
    }
    
    /**
     * Get color by hexadecimal value (using format like #000000)
     *
     * @param $color The color to be converted
     * @param $image Image to allocate color (default current)
     * @return void
     */
    protected function color_from_hex($color, $image = null) {
        if ($image === null) {
            $image = $this->image;
        }
        
        return imagecolorallocate($image,
            hexdec(substr($bgcolor, 1, 2)),
            hexdec(substr($bgcolor, 3, 2)),
            hexdec(substr($bgcolor, 5, 2))
        );
    }
    
    /**
     * Load image
     *
     * @param $path Path of image to load
     * @return resource The image resource
     */
    protected function load_image($path) {
        $info = @getimagesize($path);
        
        if ($info === false) {
            throw new Fishy_Image_Exception($this, "File not found, not acessible or invalid image type for $path");
        }
        
        $fn = $this->load_function($info[2]);
        
        if ($fn === false) {
            throw new Fishy_Image_Exception($this, "File type " . image_type_to_mime_type($info[2]) . " is not supported");
        }
        
        $image = @$fn($path);
        
        if (!$image) {
        	throw new Fishy_Image_Exception($this, "Error opening image file, probably corrupted data");
        }
        
        return $image;
    }
    
    /**
     * Get load function to use according to image type
     *
     * @param $type Type of image
     * @return string The function name
     */
    protected final function load_function($type) {
        switch ($type) {
            case 1:
                return 'imagecreatefromgif';
            case 2:
                return 'imagecreatefromjpeg';
            case 3:
                return 'imagecreatefrompng';
        }
        
        return false;
    }
    
    /**
     * Get output function to use according to image type
     *
     * @param $type Type of image
     * @return string The function name
     */
    protected final function output_function($type) {
        switch ($type) {
            case 1:
                return 'imagegif';
            case 2:
                return 'imagejpeg';
            case 3:
                return 'imagepng';
        }
        
        return false;
    }
    
    /**
     * Calculate basic data about image
     *
     * @param $image The image to caculate data
     * @param $compute_current Put image data at current information
     * @return array Array containing data
     */
    protected function prepare_data($image, $compute_current = false) {
        $data['width'] = imagesx($image);
        $data['height'] = imagesy($image);
        $data['aspect_x'] = $data['height'] / $data['width'];
        $data['aspect_y'] = $data['width'] / $data['height'];
        $data['bias'] = $data['width'] >= $data['height'] ? IMAGE_BIAS_HORIZONTAL : IMAGE_BIAS_VERTICAL;
        
        if ($compute_current) {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }
        
        return $data;
    }
}

class Fishy_Image_Exception extends Exception {
    public $image;
    
    public function __construct($image, $message) {
        parent::__construct($message);
        
        $this->image = $image;
    }
}
