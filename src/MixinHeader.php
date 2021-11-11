<?php

class MixinHeader {

  public static function assertValid(array $mixin): array {
    if (empty($mixin['mixinVersion'])) {
      throw new \RuntimeException("Invalid {$mixin["file"]}. There is no @mixinVersion annotation.");
    }
    if (empty($mixin['mixinVersion'])) {
      throw new \RuntimeException("Invalid {$mixin["file"]}. There is no @mixinName annotation.");
    }
    return $mixin;
  }

  public static function parseFile(string $file): array {
    $mixinSpec = [];
    $php = file_get_contents($file);
    foreach (token_get_all($php) as $token) {
      if (is_array($token) && in_array($token[0], [T_DOC_COMMENT, T_COMMENT, T_FUNC_C, T_METHOD_C, T_TRAIT_C, T_CLASS_C])) {
        $mixinSpec = static::parseComment($token[1]);
        break;
      }
    }
    $mixinSpec['file'] = $file;
    return $mixinSpec;
  }

  protected static function parseComment(string $comment): array {
    $info = [];
    $param = NULL;
    foreach (preg_split("/((\r?\n)|(\r\n?))/", $comment) as $num => $line) {
      if (!$num || strpos($line, '*/') !== FALSE) {
        continue;
      }
      $line = ltrim(trim($line), '*');
      if (strlen($line) && $line[0] === ' ') {
        $line = substr($line, 1);
      }
      if (strpos(ltrim($line), '@') === 0) {
        $words = explode(' ', ltrim($line, ' @'));
        $key = array_shift($words);
        $param = NULL;
        if ($key == 'var') {
          $info['type'] = explode('|', $words[0]);
        }
        elseif ($key == 'return') {
          $info['return'] = explode('|', $words[0]);
        }
        elseif ($key == 'options' || $key == 'ui_join_filters') {
          $val = str_replace(', ', ',', implode(' ', $words));
          $info[$key] = explode(',', $val);
        }
        elseif ($key == 'throws' || $key == 'see') {
          $info[$key][] = implode(' ', $words);
        }
        elseif ($key == 'param' && $words) {
          $type = $words[0][0] !== '$' ? explode('|', array_shift($words)) : NULL;
          $param = rtrim(array_shift($words), '-:()/');
          $info['params'][$param] = [
            'type' => $type,
            'description' => $words ? ltrim(implode(' ', $words), '-: ') : '',
            'comment' => '',
          ];
        }
        else {
          // Unrecognized annotation, but we'll duly add it to the info array
          $val = implode(' ', $words);
          $info[$key] = strlen($val) ? $val : TRUE;
        }
      }
      elseif ($param) {
        $info['params'][$param]['comment'] .= $line . "\n";
      }
      elseif ($num == 1) {
        $info['description'] = ucfirst($line);
      }
      elseif (!$line) {
        if (isset($info['comment'])) {
          $info['comment'] .= "\n";
        }
        else {
          $info['comment'] = NULL;
        }
      }
      // For multi-line description.
      elseif (count($info) === 1 && isset($info['description']) && substr($info['description'], -1) !== '.') {
        $info['description'] .= ' ' . $line;
      }
      else {
        $info['comment'] = isset($info['comment']) ? "{$info['comment']}\n$line" : $line;
      }
    }
    if (isset($info['comment'])) {
      $info['comment'] = rtrim($info['comment']);
    }
    return $info;
  }

}
