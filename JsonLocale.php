<?php
/*
 * Copyright (c) 2010 Jan Smitka <jan@smitka.org>
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 */


/**
 * Represents the set of localisation files.
 *
 * @author Jan Smitka <jan@smitka.org>
 */
class JsonLocale extends Object implements IEditableTranslator
{
	/** @var string */
	private $locale;

	/** @var string */
	private $name;

	/** @var array */
	private $plurals = array(0 => 1, 1 => 0, 2 => 1);

	/** @var string */
	private $strings = array();

	/** @var string */
	private $directory;

	/** @var string */
	private $currentSheet = 'main';

	/** @var array */
	private $updatedSheets = array();



	public function __construct($directory, $locale)
	{
		$this->locale = $locale;
		$this->directory = $directory;
		if (!file_exists($file = $directory . '/' . $locale . '.json'))
			throw new FileNotFoundException('File \'' . $file . '\' does not exists.');

		$info = json_decode(file_get_contents($file));
		if (isset($info->name))
			$this->name = $info->name;
		if (isset($info->plurals))
			$this->plurals = (array) $info->plurals;
		if (isset($info->defaultSheet))
			$this->currentSheet = $info->defaultSheet;
		unset($info);

		// TODO: add support for locale inheritance
	}

	/**
	 * Translates the $message. Printf-like formatting is allowed.
	 * @param string $message
	 * @param int $count
	 * @return string
	 */
	public function translate($message, $count = NULL)
	{
		$string = $this->getString($message);
		if (is_array($string)) {
			$n = 0;
			foreach ($this->plurals as $c => $i)
				if (abs((int) $count) >= $c)
					$n = $i;
				
			if (isset($string[$n]))
				$string = $string[$n];
			else
				$string = $string[0];
		}

		$args = func_get_args();
		array_shift($args);
		if (!empty($args))
			return vsprintf($string, $args);
		else
			return $string;
	}

	public function getString($string, $default = FALSE)
	{
		if (strpos($string, '/') !== FALSE)
			list($sheet, $string) = explode('/', $string, 2);
		else
			$sheet = $this->currentSheet;
		$sheet = $this->getSheet($sheet);

		if (isset($sheet->$string) && $sheet->$string)
			return $sheet->$string;
		else {
			if ($string)
				$sheet->$string = FALSE;
			return $default !== FALSE ? $default : $string;
		}
	}

	public function getCurrentSheet()
	{
		return $this->currentSheet;
	}

	public function setCurrentSheet($currentSheet)
	{
		$this->currentSheet = $currentSheet;
	}
	public function getSheet($sheet)
	{
		if (!isset($this->strings[$sheet]))
			$this->loadSheet($sheet);
		return $this->strings[$sheet];
	}

	private function loadSheet($sheet)
	{
		if (file_exists($file = $this->directory . '/' . $sheet . '.json')) {
			$this->strings[$sheet] = json_decode(file_get_contents($file));
		} else {
			$this->strings[$sheet] = (object) array();
		}
	}

	public function getVariantsCount()
	{
		return count(array_unique($this->plurals));
	}

	public function getStrings()
	{
		$stringList = array();
		foreach ($this->strings as $sheet => $strings) {
			foreach ($strings as $key => $string)
				$stringList[$sheet . '/' . $key] = $string;
		}
		ksort($stringList);
		return $stringList;
	}

	public function setTranslation($string, $value)
	{
		if (strpos($string, '/') !== FALSE)
			list($sheet, $string) = explode('/', $string, 2);
		else
			$sheet = $this->currentSheet;
		$this->updatedSheets[$sheet] = $this->getSheet($sheet);
		$sheet = $this->getSheet($sheet);
		$sheet->$string = $value;
	}

	public function save()
	{
		foreach ($this->updatedSheets as $sheet => $strings) {
			file_put_contents($this->directory . '/' . $sheet . '.json', json_encode($strings));
		}
	}

}