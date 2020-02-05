<?php

namespace PhpDocBlockChecker\Check;

use PhpDocBlockChecker\FileInfo;
use PhpDocBlockChecker\Status\StatusType\Warning\ParamMismatchWarning;
use PhpDocBlockChecker\Status\StatusType\Warning\ParamMissingWarning;

class ParamCheck extends Check
{

    /**
     * @param FileInfo $file
     */
    public function check(FileInfo $file)
    {
        foreach ($file->getMethods() as $name => $method) {
            // If the docblock is inherited, we can't check for params and return types:
            if (isset($method['docblock']['inherit']) && $method['docblock']['inherit']) {
                continue;
            }

            foreach ($method['params'] as $param => $type) {
                if (!isset($method['docblock']['params'][$param])) {
                    $this->fileStatus->add(
                        new ParamMissingWarning($file->getFileName(), $name, $method['line'], $name, $param)
                    );
                    continue;
                }

                if (!empty($type)) {
                    if (is_array($type)) {
                        $docBlockTypes = explode('|', $method['docblock']['params'][$param]);
                        sort($docBlockTypes);
                        sort($type);
                        if ($type !== $docBlockTypes) {
                            $this->fileStatus->add(
                                new ParamMismatchWarning(
                                    $file->getFileName(),
                                    $name,
                                    $method['line'],
                                    $name,
                                    $param,
                                    implode('|', $type),
                                    $method['docblock']['params'][$param]
                                )
                            );
                            continue;
                        }
                    }

                    if ($method['docblock']['params'][$param] !== $type) {
                        if ($type === 'array' && substr($method['docblock']['params'][$param], -2) === '[]') {
                            // Do nothing because this is fine.
                        } else {
                            if (!is_array($type) || !$this->checkMultipleParamStatements($method, $param)) {
                                $this->fileStatus->add(
                                    new ParamMismatchWarning(
                                        $file->getFileName(),
                                        $name,
                                        $method['line'],
                                        $name,
                                        $param,
                                        $type,
                                        $method['docblock']['params'][$param]
                                    )
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function enabled()
    {
        return !$this->config->isSkipSignatures();
    }

    /**
     * @param array $method
     * @param string $param
     * @return bool
     */
    private function checkMultipleParamStatements(array $method, $param): bool
    {
        $dockParam = explode('|', $method['docblock']['params'][$param]);
        $methodParam = $method['params'][$param];

        return count(array_diff($dockParam, $methodParam)) == 0 && count(array_diff($methodParam, $dockParam)) == 0;
    }
}
