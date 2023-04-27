<?php

class JSParser {

	protected $content;
	protected $keywords;
	protected $regs = [];
	protected $regsCounter = 0;
	protected $strings = [];
	protected $stringsCounter = 0;

	protected function _extractRegs($match) {
		$this->regs[$this->regsCounter] = $match[1];
		$id = "<<reg{$this->regsCounter}>>";
		$this->regsCounter++;
		return $id;
	}
	protected function _extractStrings($match) {
		$this->strings[$this->stringsCounter] = $this->importRegExps($match[0]);
		$id = "<<s{$this->stringsCounter}>>";
		$this->stringsCounter++;
		return $id;
	}
	protected function importRegExps($input) {
		$regs = $this->regs;
		return preg_replace_callback("#<<reg(\d+)>>#", fn($match) => $regs[$match[1]], (string) $input);
	}

	protected function importStrings($input) {
		$strings = $this->strings;
		return preg_replace_callback("#<<s(\d+)>>#", fn($match) => $strings[$match[1]], (string) $input);
	}

	public function __construct($file, $keywords = '_') {
		$this->content = file_get_contents($file);
		$this->keywords = (array)$keywords;
	}
	
	public function parse() {
		$output = $this->content; //htmlspecialchars($this->content, ENT_NOQUOTES);

		// extract reg exps
		$output = preg_replace_callback(
			'# ( / (?: (?>[^/\\\\]++) | \\\\\\\\ | (?<!\\\\)\\\\(?!\\\\) | \\\\/ )+ (?<!\\\\)/ ) [a-z]* \b #ix',
			$this->_extractRegs(...), (string) $output
		);

		// extract strings
		$output = preg_replace_callback(
			['# " ( (?: (?>[^"\\\\]++) | \\\\\\\\ | (?<!\\\\)\\\\(?!\\\\) | \\\\" )* ) (?<!\\\\)" #ix', "# ' ( (?: (?>[^'\\\\]++) | \\\\\\\\ | (?<!\\\\)\\\\(?!\\\\) | \\\\' )* ) (?<!\\\\)' #ix"], $this->_extractStrings(...), $output
		);

		// delete line comments
		$output = preg_replace("#(//.*?)$#m", '', $output);

		// delete multiline comments
		$output = preg_replace('#/\*(.*?)\*/#is', '', $output);

		$strings = $this->strings;
		$output = preg_replace_callback("#<<s(\d+)>>#", fn($match) => $strings[$match[1]], $output);

		$keywords = implode('|', $this->keywords);

		$strings = [];

		// extract func calls
		preg_match_all(
			'# (?:'.$keywords.') \(\\ *" ( (?: (?>[^"\\\\]++) | \\\\\\\\ | (?<!\\\\)\\\\(?!\\\\) | \\\\" )* ) (?<!\\\\)"\\ *\) #ix',
			$output, $matches, PREG_SET_ORDER
		);

		foreach ($matches as $m) $strings[] = stripslashes($m[1]);

		$matches = [];
		preg_match_all(
			"# (?:$keywords) \(\\ *' ( (?: (?>[^'\\\\]++) | \\\\\\\\ | (?<!\\\\)\\\\(?!\\\\) | \\\\' )* ) (?<!\\\\)'\\ *\) #ix",
			$output, $matches, PREG_SET_ORDER
		);

		foreach ($matches as $m) $strings[] = stripslashes($m[1]);

		return $strings;
	}
}
?>
