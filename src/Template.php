<?php

class Template {
	protected $rendered = null;
	protected $template;
	protected $vars = array();

	public function __construct($template, $vars = null) {
		$this->template = $template;

		if (is_array($vars)) {
			foreach ($vars as $name => $value) {
				$this->__set($name, $value);
			}
		}
	}

	public function __get($name) {
		return (isset($this->vars[$name]) ? $this->vars[$name] : null);
	}

	public function __set($name, $value) {
		if ($name === 'this') {
			throw new Exception("'this' is not an allowed name for a template variable");
		}
		$this->vars[$name] = $value;
		$this->rendered = null;
	}
	
	public function __toString() {
		return $this->toString();
	}
	
	public function toString() {
		return $this->render();
	}
	
	public function file($template_name) {
		$included_files = get_included_files();
		$template_dir = dirname(array_pop($included_files)); // what is this? 
		include "{$template_dir}/{$template_name}"; // what if this fails?
	}
	
	// @TODO rename to render()
	public function render() {
		if ($this->rendered !== null || $this->template === null) {
			return $this->rendered;
		}

		if ($this->vars) {
			$__vars__ = array();
			foreach ($this->vars as $name => $value) {
				$__vars__[$name] = ($value instanceof Template ? $value->render() : $value);
			}

			unset($name, $value);
			extract($__vars__, EXTR_REFS);
			unset($__vars__);
		}

		ob_start();

		// change error reporting to hide notices (for unset variables)
		// @TODO make this configurable
		//$__error_reporting__ = error_reporting(error_reporting() ^ E_NOTICE);

		if (!@include $this->template) {
			if (strpos($this->template, '<?php ') === false) {
				echo $this->template;
			} else {
				eval('?>' . $this->template);
				// hide all errors??
				//$eval = eval($this->template);
				//if ($eval === false) {
				//	ob_clean();
				//}
			}
		}

		// reset error reporting
		//error_reporting($__error_reporting__);
		return $this->rendered = ob_get_clean();
	}

}
