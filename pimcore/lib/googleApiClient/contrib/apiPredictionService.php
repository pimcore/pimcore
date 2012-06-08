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
   * The "trainedmodels" collection of methods.
   * Typical usage is:
   *  <code>
   *   $predictionService = new apiPredictionService(...);
   *   $trainedmodels = $predictionService->trainedmodels;
   *  </code>
   */
  class TrainedmodelsServiceResource extends apiServiceResource {


    /**
     * Submit model id and request a prediction (trainedmodels.predict)
     *
     * @param string $id The unique name for the predictive model.
     * @param Input $postBody
     * @return Output
     */
    public function predict($id, Input $postBody, $optParams = array()) {
      $params = array('id' => $id, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('predict', array($params));
      if ($this->useObjects()) {
        return new Output($data);
      } else {
        return $data;
      }
    }
    /**
     * Begin training your model. (trainedmodels.insert)
     *
     * @param Training $postBody
     * @return Training
     */
    public function insert(Training $postBody, $optParams = array()) {
      $params = array('postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Training($data);
      } else {
        return $data;
      }
    }
    /**
     * Check training status of your model. (trainedmodels.get)
     *
     * @param string $id The unique name for the predictive model.
     * @return Training
     */
    public function get($id, $optParams = array()) {
      $params = array('id' => $id);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Training($data);
      } else {
        return $data;
      }
    }
    /**
     * Add new data to a trained model. (trainedmodels.update)
     *
     * @param string $id The unique name for the predictive model.
     * @param Update $postBody
     * @return Training
     */
    public function update($id, Update $postBody, $optParams = array()) {
      $params = array('id' => $id, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Training($data);
      } else {
        return $data;
      }
    }
    /**
     * Delete a trained model. (trainedmodels.delete)
     *
     * @param string $id The unique name for the predictive model.
     */
    public function delete($id, $optParams = array()) {
      $params = array('id' => $id);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
  }

  /**
   * The "hostedmodels" collection of methods.
   * Typical usage is:
   *  <code>
   *   $predictionService = new apiPredictionService(...);
   *   $hostedmodels = $predictionService->hostedmodels;
   *  </code>
   */
  class HostedmodelsServiceResource extends apiServiceResource {


    /**
     * Submit input and request an output against a hosted model. (hostedmodels.predict)
     *
     * @param string $hostedModelName The name of a hosted model.
     * @param Input $postBody
     * @return Output
     */
    public function predict($hostedModelName, Input $postBody, $optParams = array()) {
      $params = array('hostedModelName' => $hostedModelName, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('predict', array($params));
      if ($this->useObjects()) {
        return new Output($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Prediction (v1.4).
 *
 * <p>
 * Lets you access a cloud hosted machine learning service that makes it easy to build smart apps
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="http://code.google.com/apis/predict/docs/developer-guide.html" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class apiPredictionService extends apiService {
  public $trainedmodels;
  public $hostedmodels;
  /**
   * Constructs the internal representation of the Prediction service.
   *
   * @param apiClient apiClient
   */
  public function __construct(apiClient $apiClient) {
    $this->restBasePath = '/prediction/v1.4/';
    $this->version = 'v1.4';
    $this->serviceName = 'prediction';

    $apiClient->addService($this->serviceName, $this->version);
    $this->trainedmodels = new TrainedmodelsServiceResource($this, $this->serviceName, 'trainedmodels', json_decode('{"methods": {"predict": {"scopes": ["https://www.googleapis.com/auth/prediction"], "parameters": {"id": {"required": true, "type": "string", "location": "path"}}, "request": {"$ref": "Input"}, "id": "prediction.trainedmodels.predict", "httpMethod": "POST", "path": "trainedmodels/{id}/predict", "response": {"$ref": "Output"}}, "insert": {"scopes": ["https://www.googleapis.com/auth/devstorage.read_only", "https://www.googleapis.com/auth/prediction"], "request": {"$ref": "Training"}, "response": {"$ref": "Training"}, "httpMethod": "POST", "path": "trainedmodels", "id": "prediction.trainedmodels.insert"}, "delete": {"scopes": ["https://www.googleapis.com/auth/prediction"], "parameters": {"id": {"required": true, "type": "string", "location": "path"}}, "httpMethod": "DELETE", "path": "trainedmodels/{id}", "id": "prediction.trainedmodels.delete"}, "update": {"scopes": ["https://www.googleapis.com/auth/prediction"], "parameters": {"id": {"required": true, "type": "string", "location": "path"}}, "request": {"$ref": "Update"}, "id": "prediction.trainedmodels.update", "httpMethod": "PUT", "path": "trainedmodels/{id}", "response": {"$ref": "Training"}}, "get": {"scopes": ["https://www.googleapis.com/auth/prediction"], "parameters": {"id": {"required": true, "type": "string", "location": "path"}}, "id": "prediction.trainedmodels.get", "httpMethod": "GET", "path": "trainedmodels/{id}", "response": {"$ref": "Training"}}}}', true));
    $this->hostedmodels = new HostedmodelsServiceResource($this, $this->serviceName, 'hostedmodels', json_decode('{"methods": {"predict": {"scopes": ["https://www.googleapis.com/auth/prediction"], "parameters": {"hostedModelName": {"required": true, "type": "string", "location": "path"}}, "request": {"$ref": "Input"}, "id": "prediction.hostedmodels.predict", "httpMethod": "POST", "path": "hostedmodels/{hostedModelName}/predict", "response": {"$ref": "Output"}}}}', true));

  }
}

class Input extends apiModel {
  protected $__inputType = 'InputInput';
  protected $__inputDataType = '';
  public $input;
  public function setInput(InputInput $input) {
    $this->input = $input;
  }
  public function getInput() {
    return $this->input;
  }
}

class InputInput extends apiModel {
  public $csvInstance;
  public function setCsvInstance(/* array(object) */ $csvInstance) {
    $this->assertIsArray($csvInstance, 'object', __METHOD__);
    $this->csvInstance = $csvInstance;
  }
  public function getCsvInstance() {
    return $this->csvInstance;
  }
}

class Output extends apiModel {
  public $kind;
  public $outputLabel;
  public $id;
  protected $__outputMultiType = 'OutputOutputMulti';
  protected $__outputMultiDataType = 'array';
  public $outputMulti;
  public $outputValue;
  public $selfLink;
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setOutputLabel($outputLabel) {
    $this->outputLabel = $outputLabel;
  }
  public function getOutputLabel() {
    return $this->outputLabel;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setOutputMulti(/* array(OutputOutputMulti) */ $outputMulti) {
    $this->assertIsArray($outputMulti, 'OutputOutputMulti', __METHOD__);
    $this->outputMulti = $outputMulti;
  }
  public function getOutputMulti() {
    return $this->outputMulti;
  }
  public function setOutputValue($outputValue) {
    $this->outputValue = $outputValue;
  }
  public function getOutputValue() {
    return $this->outputValue;
  }
  public function setSelfLink($selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
}

class OutputOutputMulti extends apiModel {
  public $score;
  public $label;
  public function setScore($score) {
    $this->score = $score;
  }
  public function getScore() {
    return $this->score;
  }
  public function setLabel($label) {
    $this->label = $label;
  }
  public function getLabel() {
    return $this->label;
  }
}

class Training extends apiModel {
  public $kind;
  public $storageDataLocation;
  public $storagePMMLModelLocation;
  protected $__dataAnalysisType = 'TrainingDataAnalysis';
  protected $__dataAnalysisDataType = '';
  public $dataAnalysis;
  public $trainingStatus;
  protected $__modelInfoType = 'TrainingModelInfo';
  protected $__modelInfoDataType = '';
  public $modelInfo;
  public $storagePMMLLocation;
  public $id;
  public $selfLink;
  public $utility;
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setStorageDataLocation($storageDataLocation) {
    $this->storageDataLocation = $storageDataLocation;
  }
  public function getStorageDataLocation() {
    return $this->storageDataLocation;
  }
  public function setStoragePMMLModelLocation($storagePMMLModelLocation) {
    $this->storagePMMLModelLocation = $storagePMMLModelLocation;
  }
  public function getStoragePMMLModelLocation() {
    return $this->storagePMMLModelLocation;
  }
  public function setDataAnalysis(TrainingDataAnalysis $dataAnalysis) {
    $this->dataAnalysis = $dataAnalysis;
  }
  public function getDataAnalysis() {
    return $this->dataAnalysis;
  }
  public function setTrainingStatus($trainingStatus) {
    $this->trainingStatus = $trainingStatus;
  }
  public function getTrainingStatus() {
    return $this->trainingStatus;
  }
  public function setModelInfo(TrainingModelInfo $modelInfo) {
    $this->modelInfo = $modelInfo;
  }
  public function getModelInfo() {
    return $this->modelInfo;
  }
  public function setStoragePMMLLocation($storagePMMLLocation) {
    $this->storagePMMLLocation = $storagePMMLLocation;
  }
  public function getStoragePMMLLocation() {
    return $this->storagePMMLLocation;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setSelfLink($selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
  public function setUtility(/* array(double) */ $utility) {
    $this->assertIsArray($utility, 'double', __METHOD__);
    $this->utility = $utility;
  }
  public function getUtility() {
    return $this->utility;
  }
}

class TrainingDataAnalysis extends apiModel {
  public $warnings;
  public function setWarnings(/* array(string) */ $warnings) {
    $this->assertIsArray($warnings, 'string', __METHOD__);
    $this->warnings = $warnings;
  }
  public function getWarnings() {
    return $this->warnings;
  }
}

class TrainingModelInfo extends apiModel {
  public $confusionMatrixRowTotals;
  public $numberLabels;
  public $confusionMatrix;
  public $meanSquaredError;
  public $modelType;
  public $numberInstances;
  public $classWeightedAccuracy;
  public $classificationAccuracy;
  public function setConfusionMatrixRowTotals($confusionMatrixRowTotals) {
    $this->confusionMatrixRowTotals = $confusionMatrixRowTotals;
  }
  public function getConfusionMatrixRowTotals() {
    return $this->confusionMatrixRowTotals;
  }
  public function setNumberLabels($numberLabels) {
    $this->numberLabels = $numberLabels;
  }
  public function getNumberLabels() {
    return $this->numberLabels;
  }
  public function setConfusionMatrix($confusionMatrix) {
    $this->confusionMatrix = $confusionMatrix;
  }
  public function getConfusionMatrix() {
    return $this->confusionMatrix;
  }
  public function setMeanSquaredError($meanSquaredError) {
    $this->meanSquaredError = $meanSquaredError;
  }
  public function getMeanSquaredError() {
    return $this->meanSquaredError;
  }
  public function setModelType($modelType) {
    $this->modelType = $modelType;
  }
  public function getModelType() {
    return $this->modelType;
  }
  public function setNumberInstances($numberInstances) {
    $this->numberInstances = $numberInstances;
  }
  public function getNumberInstances() {
    return $this->numberInstances;
  }
  public function setClassWeightedAccuracy($classWeightedAccuracy) {
    $this->classWeightedAccuracy = $classWeightedAccuracy;
  }
  public function getClassWeightedAccuracy() {
    return $this->classWeightedAccuracy;
  }
  public function setClassificationAccuracy($classificationAccuracy) {
    $this->classificationAccuracy = $classificationAccuracy;
  }
  public function getClassificationAccuracy() {
    return $this->classificationAccuracy;
  }
}

class Update extends apiModel {
  public $csvInstance;
  public $label;
  public function setCsvInstance(/* array(object) */ $csvInstance) {
    $this->assertIsArray($csvInstance, 'object', __METHOD__);
    $this->csvInstance = $csvInstance;
  }
  public function getCsvInstance() {
    return $this->csvInstance;
  }
  public function setLabel($label) {
    $this->label = $label;
  }
  public function getLabel() {
    return $this->label;
  }
}
