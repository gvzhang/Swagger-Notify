<?php

/**
 * 转化成swagger格式的JSON
 */

namespace App;

class SwaggerJson
{
    const REPLACE_VAR = "##VAR##";
    const REPLACE_MODULE_VAR = "##MODULE##";
    const REPLACE_DEFINITIONS_VAR = "##DEFINITIONS##";

    private $_target;
    private $_diffData;
    private $_jsonTplPath;
    private $_htmlTplPath;
    private $_jsonDir;
    private $_htmlDir;

    public function __construct($data, $target, $refData)
    {
        $this->_target = $target;
        $this->_diffData = $data;
        $this->_refData = $refData;
        if (!is_dir($this->_target)) {
            throw new \InvalidArgumentException("target Error");
        }
        if (empty($this->_diffData)) {
            throw new \InvalidArgumentException("diffData Error");
        }
        $this->_jsonTplPath = rootPath() . "/template/swagger.json.tpl";
        $this->_htmlTplPath = rootPath() . "/template/swagger.html.tpl";
        $this->_jsonDir = "api-json";
        $this->_htmlDir = "";
    }

    /**
     * 生成Swagger JSON模板
     */
    public function generate()
    {
        $htmlFileList = [];
        $tplData = $this->_getTplData();
        $jsonTpl = $this->_getJsonTpl();
        $htmlTpl = $this->_getHtmlTpl();
        foreach ($tplData as $data) {
            $file = pathinfo($data["file"]);
            $module = explode("_", $file["filename"]);
            $module = count($module) == 2 ? $module[1] : "";

            // 写入JSON数据
            $putContent = str_replace(self::REPLACE_VAR, $data["var"], $jsonTpl);
            $putContent = str_replace(self::REPLACE_DEFINITIONS_VAR, $data["ref"], $putContent);
            $putContent = json_decode(str_replace(self::REPLACE_MODULE_VAR, $module, $putContent));
            $jsonSavePath = $this->_jsonDir . "/" . $file["filename"] . "-" . time() . ".json";
            file_put_contents($this->_target . "/" . $jsonSavePath, json_encode($putContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            // 写入HTML数据
            $putContent = str_replace(self::REPLACE_VAR, $jsonSavePath, $htmlTpl);
            $htmlFileName = $file["filename"] . "-" . time() . ".html";
            file_put_contents($this->_target . "/" . $this->_htmlDir . "/" . $htmlFileName, $putContent);
            array_push($htmlFileList, $htmlFileName);
        }
        return $htmlFileList;
    }

    /**
     * 获取替换数据
     */
    private function _getTplData()
    {
        $diffPaths = [];
        foreach ($this->_diffData as $file => $changeList) {
            $tplData = [];
            $tplData["ref"] = json_encode($this->_refData[$file], JSON_UNESCAPED_UNICODE);

            $file = pathinfo($file);
            $tplData["file"] = $file['basename'];
            $tplData["var"] = "";
            foreach ($changeList as $jsonData) {
                $jsonDataStr = json_encode($jsonData, JSON_UNESCAPED_UNICODE);
                $tplData["var"] .= substr($jsonDataStr, 1, strlen($jsonDataStr) - 2) . ",";
            }
            $tplData["var"] = rtrim($tplData["var"], ",");
            array_push($diffPaths, $tplData);
        }
        return $diffPaths;
    }

    /**
     * 获取JSON模板信息
     */
    private function _getJsonTpl()
    {
        return file_get_contents($this->_jsonTplPath);
    }

    /**
     * 获取HTML模板信息
     */
    private function _getHtmlTpl()
    {
        return file_get_contents($this->_htmlTplPath);
    }
}