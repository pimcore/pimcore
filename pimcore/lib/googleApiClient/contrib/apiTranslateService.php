<?php
/*
 * Copyright 2010 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */


  /**
   * The "languages" collection of methods.
   * Typical usage is:
   *  <code>
   *   $translateService = new apiTranslateService(...);
   *   $languages = $translateService->languages;
   *  </code>
   */
  class LanguagesServiceResource extends apiServiceResource {


    /**
     * List the source/target languages supported by the API (languages.list)
     *
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string target the language and collation in which the localized results should be returned
     * @return LanguagesListResponse
     */
    public function listLanguages($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new LanguagesListResponse($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "detections" collection of methods.
   * Typical usage is:
   *  <code>
   *   $translateService = new apiTranslateService(...);
   *   $detections = $translateService->detections;
   *  </code>
   */
  class DetectionsServiceResource extends apiServiceResource {


    /**
     * Detect the language of text. (detections.list)
     *
     * @param string $q The text to detect
     * @return DetectionsListResponse
     */
    public function listDetections($q, $optParams = array()) {
      $params = array('q' => $q);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new DetectionsListResponse($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "translations" collection of methods.
   * Typical usage is:
   *  <code>
   *   $translateService = new apiTranslateService(...);
   *   $translations = $translateService->translations;
   *  </code>
   */
  class TranslationsServiceResource extends apiServiceResource {


    /**
     * Returns text translations from one language to another. (translations.list)
     *
     * @param string $q The text to translate
     * @param string $target The target language into which the text should be translated
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string source The source language of the text
     * @opt_param string format The format of the text
     * @opt_param string cid The customization id for translate
     * @return TranslationsListResponse
     */
    public function listTranslations($q, $target, $optParams = array()) {
      $params = array('q' => $q, 'target' => $target);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new TranslationsListResponse($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Translate (v2).
 *
 * <p>
 * Lets you translate text from one language to another
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="http://code.google.com/apis/language/translate/v2/using_rest.html" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class apiTranslateService extends apiService {
  public $languages;
  public $detections;
  public $translations;
  /**
   * Constructs the internal representation of the Translate service.
   *
   * @param apiClient apiClient
   */
  public function __construct(apiClient $apiClient) {
    $this->restBasePath = '/language/translate/';
    $this->version = 'v2';
    $this->serviceName = 'translate';

    $apiClient->addService($this->serviceName, $this->version);
    $this->languages = new LanguagesServiceResource($this, $this->serviceName, 'languages', json_decode('{"methods": {"list": {"parameters": {"target": {"type": "string", "location": "query"}}, "id": "language.languages.list", "httpMethod": "GET", "path": "v2/languages", "response": {"$ref": "LanguagesListResponse"}}}}', true));
    $this->detections = new DetectionsServiceResource($this, $this->serviceName, 'detections', json_decode('{"methods": {"list": {"parameters": {"q": {"repeated": true, "required": true, "type": "string", "location": "query"}}, "id": "language.detections.list", "httpMethod": "GET", "path": "v2/detect", "response": {"$ref": "DetectionsListResponse"}}}}', true));
    $this->translations = new TranslationsServiceResource($this, $this->serviceName, 'translations', json_decode('{"methods": {"list": {"parameters": {"q": {"repeated": true, "required": true, "type": "string", "location": "query"}, "source": {"type": "string", "location": "query"}, "cid": {"repeated": true, "type": "string", "location": "query"}, "target": {"required": true, "type": "string", "location": "query"}, "format": {"enum": ["html", "text"], "type": "string", "location": "query"}}, "id": "language.translations.list", "httpMethod": "GET", "path": "v2", "response": {"$ref": "TranslationsListResponse"}}}}', true));

  }
}

class DetectionsListResponse extends apiModel {
  protected $__detectionsType = 'DetectionsResourceItems';
  protected $__detectionsDataType = 'array';
  public $detections;
  public function setDetections(/* array(DetectionsResourceItems) */ $detections) {
    $this->assertIsArray($detections, 'DetectionsResourceItems', __METHOD__);
    $this->detections = $detections;
  }
  public function getDetections() {
    return $this->detections;
  }
}

class DetectionsResource extends apiModel {
}

class DetectionsResourceItems extends apiModel {
  public $isReliable;
  public $confidence;
  public $language;
  public function setIsReliable($isReliable) {
    $this->isReliable = $isReliable;
  }
  public function getIsReliable() {
    return $this->isReliable;
  }
  public function setConfidence($confidence) {
    $this->confidence = $confidence;
  }
  public function getConfidence() {
    return $this->confidence;
  }
  public function setLanguage($language) {
    $this->language = $language;
  }
  public function getLanguage() {
    return $this->language;
  }
}

class LanguagesListResponse extends apiModel {
  protected $__languagesType = 'LanguagesResource';
  protected $__languagesDataType = 'array';
  public $languages;
  public function setLanguages(/* array(LanguagesResource) */ $languages) {
    $this->assertIsArray($languages, 'LanguagesResource', __METHOD__);
    $this->languages = $languages;
  }
  public function getLanguages() {
    return $this->languages;
  }
}

class LanguagesResource extends apiModel {
  public $name;
  public $language;
  public function setName($name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setLanguage($language) {
    $this->language = $language;
  }
  public function getLanguage() {
    return $this->language;
  }
}

class TranslationsListResponse extends apiModel {
  protected $__translationsType = 'TranslationsResource';
  protected $__translationsDataType = 'array';
  public $translations;
  public function setTranslations(/* array(TranslationsResource) */ $translations) {
    $this->assertIsArray($translations, 'TranslationsResource', __METHOD__);
    $this->translations = $translations;
  }
  public function getTranslations() {
    return $this->translations;
  }
}

class TranslationsResource extends apiModel {
  public $detectedSourceLanguage;
  public $translatedText;
  public function setDetectedSourceLanguage($detectedSourceLanguage) {
    $this->detectedSourceLanguage = $detectedSourceLanguage;
  }
  public function getDetectedSourceLanguage() {
    return $this->detectedSourceLanguage;
  }
  public function setTranslatedText($translatedText) {
    $this->translatedText = $translatedText;
  }
  public function getTranslatedText() {
    return $this->translatedText;
  }
}
