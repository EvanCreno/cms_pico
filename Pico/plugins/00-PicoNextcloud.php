<?php


/**
 * CMS Pico - Integration of Pico within your files to create websites.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
 * @license GNU AGPL version 3 or any later version
 *
 * Based on Pico dummy plugin:
 * @author  Daniel Rudolf
 * @link    http://picocms.org
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 1.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
final class PicoNextcloud extends AbstractPicoPlugin {
	/** @var array */
	private $config;

	/** @var HTMLPurifier */
	private $htmlPurifier;


	/**
	 * We don't want anyone to disable this plugin.
	 *
	 * @param bool $enabled
	 * @param bool $recursive
	 * @param bool $auto
	 */
	public function setEnabled($enabled, $recursive = true, $auto = false) {
		if (!$enabled) {
			throw new RuntimeException('PicoNextcloud plugin must not be disabled');
		}

		parent::setEnabled($enabled, $recursive, $auto);
	}

	/**
	 * Loading stuff.
	 *
	 * @see    Pico::getConfig()
	 *
	 * @param  array &$config array of config variables
	 *
	 * @return void
	 */
	public function onConfigLoaded(array &$config) {
		$this->htmlPurifier = new HTMLPurifier(HTMLPurifier_Config::createDefault());
	}


	/**
	 * Triggered after Pico has evaluated the request URL
	 *
	 * @see    Pico::getRequestUrl()
	 * @param  string &$url part of the URL describing the requested contents
	 * @return void
	 */
	public function onRequestUrl(&$url)
	{
		$pluginConfig = $this->getConfig('PicoNextcloud');
		$ncSiteId = !empty($pluginConfig['site_id']) ? $pluginConfig['site_id'] : '';

		$requestUri = \OC::$server->getRequest()->getRawPathInfo();
		if ($requestUri && $ncSiteId) {
			$baseRequestUri = '/apps/cms_pico/pico/' . $ncSiteId . '/';
			$baseRequestUriLength = strlen($baseRequestUri);
			if (substr($requestUri, 0, $baseRequestUriLength) === $baseRequestUri) {
				$url = substr($requestUri, $baseRequestUriLength);
			}
		}
	}


	/**
	 * purify all entries from meta.
	 *
	 * @param  string[] &$meta parsed meta data
	 *
	 * @return void
	 */
	public function onMetaParsed(array &$meta) {
		$newMeta = $this->parseArray($meta);
		$meta = $newMeta;
	}


	/**
	 * @param array $meta
	 *
	 * @return array
	 */
	private function parseArray(array $meta) {
		$newMeta = [];
		foreach ($meta as $key => $value) {
			if (is_array($value)) {
				$newMeta[$key] = $this->parseArray($value);
			} else {
				$newMeta[$key] = $this->htmlPurifier->purify($value);
			}
		}

		return $newMeta;
	}


	/**
	 * Purify the content from the page.
	 *
	 * @see    Pico::getFileContent()
	 *
	 * @param  string &$content parsed contents
	 *
	 * @return void
	 */
	public function onContentParsed(&$content) {
		$content = $this->htmlPurifier->purify($content);
	}


	/**
	 * We force the theme_url so user cannot set his own theme.
	 *
	 * @see    Pico::getTwig()
	 * @see    DummyPlugin::onPageRendered()
	 *
	 * @param  Twig_Environment &$twig twig template engine
	 * @param  array &$twigVariables template variables
	 * @param  string &$templateName file name of the template
	 *
	 * @return void
	 */
	public function onPageRendering(Twig_Environment &$twig, array &$twigVariables, &$templateName) {
		$twigVariables['theme_url'] = OC_App::getAppWebPath('cms_pico') . '/Pico/themes/' . $this->getConfig('theme');
	}
}