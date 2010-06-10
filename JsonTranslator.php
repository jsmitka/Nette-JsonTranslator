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



require_once dirname(__FILE__) . '/JsonLocale.php';

require_once dirname(__FILE__) . '/exceptions.php';

if (!interface_exists('IEditableTranslator', FALSE)) {
	require_once dirname(__FILE__) . '/IEditableTranslator/IEditableTranslator.php';
}


/**
 * Simple translator, which uses JSON for storing the localisation data.
 * Translation Panel is supported!
 *
 * @author Jan Smitka <jan@smitka.org>
 */
class JsonTranslator extends Object implements IEditableTranslator
{
	/** @var string Directory, where localisation files reside */
	private $l10nDir = '%appDir%/l10n';

	/** @var string */
	private $currentLocale = 'en_GB';

	/** @var array */
	private $locales = array();

	/** @var JsonTranslator */
	private static $translator;


	/**
	 * Translates the $message. Translation is handled by the ITranslator service.
	 * @param string $message
	 * @param int $count
	 * @return string
	 */
	static public function l($message, $count = NULL)
	{
		if (self::$translator === NULL)
			self::$translator = Environment::getService('ITranslator');
		return call_user_func_array(array(self::$translator, 'translate'), func_get_args());
	}

	/**
	 * Sets the current sheet. Default is 'main'.
	 * @param string $sheet
	 * @return
	 */
	static public function ln($sheet)
	{
		if (self::$translator === NULL)
			self::$translator = Environment::getService('ITranslator');
		return self::$translator->setCurrentSheet($sheet);
	}


	public function __construct($locale = NULL)
	{
		if ($locale === NULL)
			$this->currentLocale = Environment::getVariable('lang');
		else
			$this->currentLocale = $locale;
	}

	public function getL10nDir()
	{
		return $this->l10nDir;
	}

	public function getCurrentLocale()
	{
		return $this->currentLocale;
	}


	/**
	 * Translates the $message. Translation is handled by the underlying JsonLocale.
	 * @param string $message
	 * @param int $count
	 * @return string
	 * @see JsonLocale::translate()
	 */
	public function translate($message, $count = NULL)
	{
		if ($message instanceof Html)
			return $message;
		return call_user_func_array(array($this->getLocale(), 'translate'), func_get_args());
	}

	/** @return JsonLocale */
	public function getLocale($locale = NULL)
	{
		if ($locale === NULL)
			$locale = $this->currentLocale;

		if (!isset($this->locales[$locale])) {
			$this->loadLocale($locale);
		}

		return $this->locales[$locale];
	}


	private function loadLocale($locale)
	{
		try {
			$this->locales[$locale] = new JsonLocale(Environment::expand($this->l10nDir) . '/' . $locale, $locale);
		} catch (FileNotFoundException $e) {
			throw new JsonTranslatorLocaleException('Failed to load locale ' . $locale . '.', 0, $e);
		}
	}


	public function setCurrentSheet($namespace)
	{
		$this->getLocale()->setCurrentSheet($namespace);
	}


	/********************* Editor features *********************/

	public function getVariantsCount()
	{
		return $this->getLocale()->getVariantsCount();
	}

	public function getStrings()
	{
		return $this->getLocale()->getStrings();
	}

	public function setTranslation($message, $string)
	{
		$this->getLocale()->setTranslation($message, $string);
	}

	public function save()
	{
		$this->getLocale()->save();
	}
}
