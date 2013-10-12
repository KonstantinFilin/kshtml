<?php

    class HtmlForm_CaptchaBlock
    {
        private $debugGraph; // Выводить сообщения об ошибках вместо изображения, 0|1
        private $jump;  // На сколько точек будут прыгать символы
        private $charFuzzy; // Сколько процентов точек попадут в контур
        private $outlineFuzzy; // Размытость контура, точки
        private $imageSize; // Выходной размер изображения, точки
        private $pointsAmount; // Сколько точек формировать на каждую строку изображения
        private $charsAmount; // Сколько символов формировать, штук
        private $imageScale; // Масштабирование изображения от исходного размера (100х100), точки

        function __construct()
        {
            $this->debugGraph = 0;
            $this->jump = 2;
            $this->charFuzzy = 80;
            $this->outlineFuzzy = 1;
            $this->imageSize = 33;
            $this->pointsAmount = $this->imageSize;
            $this->charsAmount = 5;
            $this->imageScale = round(10./$this->imageSize, 2);
        }

        public function setDebugGraph($debugGraph)
        {
            $this->debugGraph = $debugGraph;
        }

        public function setJump($jump)
        {
            $this->jump = $jump;
        }

        public function setCharFuzzy($charFuzzy)
        {
            $this->charFuzzy = $charFuzzy;
        }

        public function setOutlineFuzzy($outlineFuzzy)
        {
            $this->outlineFuzzy = $outlineFuzzy;
        }

        public function setImageSize($imageSize)
        {
            $this->imageSize = $imageSize;
        }

        public function setPointsAmount($pointsAmount)
        {
            $this->pointsAmount = $pointsAmount;
        }

        public function setCharsAmount($charsAmount)
        {
            $this->charsAmount = $charsAmount;
        }

        public function setImageScale($imageScale)
        {
            $this->imageScale = $imageScale;
        }

        public function get()
        {
            $im = imagecreate($this->imageSize * $this->charsAmount, $this->imageSize);

            if(!$im)
            {
                throw new Exception("Can't create image");
            }

            $x = 0;
            $y = 0;

            $color = imagecolorallocate ($im, 0, 0, 255);
            $colorWhite = imagecolorallocate ($im, 255, 255, 255);
            $colorBlack = imagecolorallocate ($im, 0, 0, 0);

            imagefill($im, $this->imageSize*$this->charsAmount - 1, $this->imageSize -  1, $colorWhite);

            $chars = $this->getSecretKey();

            // Перебираем символы изображения
            for($imNum = 0; $imNum < $this->charsAmount; $imNum++)
            {
                $outline = array();
                $char = $chars[$imNum];

                $updown = rand($this->jump*(-1), $this->jump);
                $shift = rand(-20, 20);

                // Масштабирование координат
                foreach($char as $k => $v)
                {
                    foreach($v as $kk => $vv)
                    {
                        $v[$kk][0] += $shift;
                        $v[$kk][1] += $shift;

                        $v[$kk][0] *= $this->imageScale;
                        $v[$kk][1] *= $this->imageScale;
                    }

                    $outline[$k*$this->imageScale+$updown] = $v;
                }

                // Проходим по координате у
                for($yt = 0; $yt < $this->imageSize; $yt++)
                {
                // Если на этой строке надо рисовать символ
                    if(array_key_exists($yt, $outline))
                    {
                        $areasWidth = array();
                        $areaWidthTotal = 0;

                        // Считаем ширину областей контура и общее количество точек,
                        // принадлежащее контуру на этой строке
                        foreach($outline[$yt] as $k=>$v)
                        {
                            $outline[$yt][$k][0] = $v[0];
                            $outline[$yt][$k][1] = $v[1];

                            $diff = $outline[$yt][$k][1] - $outline[$yt][$k][0];
                            $areasWidth[$k] = $diff;
                            $areaWidthTotal += $diff;
                        }

                        // Бездумно ставим POINTS_AMOUNT точек на координате $yt
                        for($a=0; $a<$this->pointsAmount; $a++)
                        {
                            if(rand(0, 100) < $this->charFuzzy)
                            {
                            // Координаты случайной точки внутри контура
                                $x = rand(0, $areaWidthTotal);

                                foreach($areasWidth as $k => $v)
                                {
                                    if($v < $x)
                                    {
                                        $x -= $v;
                                    }
                                    else
                                    {
                                        $x += $outline[$yt][$k][0];
                                        $x += rand(-$this->outlineFuzzy, $this->outlineFuzzy); // Размытие границы контура
                                        $x += $imNum*$this->imageSize; // Перемещаем символ на место номер $this->imageSize
                                        break;
                                    }
                                }
                            }
                            else
                            {
                            // Координаты случайной точки снаружи контура
                                $x = rand(0, $this->imageSize-$areaWidthTotal);
                                $buff = '';

                                // Ставим точку вне контура
                                foreach($outline[$yt] as $k=>$v)
                                {
                                    if($x > $v[0])
                                    {
                                        $x += $v[1]-$v[0];
                                    }
                                }

                                // Размываем изображение, перемещаем символ на место номер $this->imageSize
                                $x += rand(-$this->outlineFuzzy, $this->outlineFuzzy) + $imNum*$this->imageSize;
                            }

                            imagesetpixel($im, $x, $yt, $color);
                        }
                    }
                    else
                    {
                    // Если на строке $yt нет контура
                        for($a=0; $a<$this->pointsAmount; $a++)
                        {
                            if(rand(0,100) > $this->charFuzzy)
                            {
                                $x = rand(0, $this->imageSize) + $imNum*$this->imageSize;
                                imagesetpixel($im, $x, $yt, $color);
                            }
                        }
                    }
                }
            }

            // Однопиксельная рамка вокруг изображения
            imageline($im, 0, 0, $this->imageSize*$this->charsAmount-1, 0, $colorBlack);
            imageline($im, $this->imageSize*$this->charsAmount-1, 0, $this->imageSize*$this->charsAmount-1, $this->imageSize-1, $colorBlack);
            imageline($im, 0, $this->imageSize-1, $this->imageSize*$this->charsAmount-1, $this->imageSize-1, $colorBlack);
            imageline($im, 0, $this->imageSize-1, 0, 0, $colorBlack);

            ob_start();
            if(!$this->debugGraph) imagepng($im);
            imagedestroy($im);
            $content = ob_get_contents();
            ob_end_clean();

            return $content;
        }

        private function getSecretKey()
        {
            $chars = array();
            $alphabet = $this->getOutlines();
            $len = count($alphabet);

            $key = '';

            for($a = 0; $a<$this->charsAmount; $a++)
            {
                $k = rand(0, $len-1);
                $chars[$a] = $alphabet[$k]; // В элементах массива хранится описание CHARS_AMOUNT символов, выбранных случайным образом
                $key .= $k;
            }

            $_SESSION['captcha'] = $key;

            return $chars;
        }

        private function getOutlines()
        {
            $char_1 = array(
              '8' => Array
              (
                '0' => Array
                (
                  '0' => 48,
                  '1' => 52
                )
              ),

              '9' => Array
              (
                '0' => Array
                (
                  '0' => 46,
                  '1' => 54
                )
              ),

              '10' => Array
              (
                '0' => Array
                (
                  '0' => 45,
                  '1' => 55
                )
              ),

              '11' => Array
              (
                '0' => Array
                (
                  '0' => 45,
                  '1' => 56
                )
              ),

              '12' => Array
              (
                '0' => Array
                (
                  '0' => 44,
                  '1' => 56
                )
              ),

              '13' => Array
              (
                '0' => Array
                (
                  '0' => 44,
                  '1' => 56
                )
              ),

              '14' => Array
              (
                '0' => Array
                (
                  '0' => 44,
                  '1' => 57
                )
              ),

              '15' => Array
              (
                '0' => Array
                (
                  '0' => 43,
                  '1' => 57
                )
              ),

              '16' => Array
              (
                '0' => Array
                (
                  '0' => 43,
                  '1' => 57
                )
              ),

              '17' => Array
              (
                '0' => Array
                (
                  '0' => 43,
                  '1' => 57
                )
              ),

              '18' => Array
              (
                '0' => Array
                (
                  '0' => 42,
                  '1' => 56
                )
              ),

              '19' => Array
              (
                '0' => Array
                (
                  '0' => 42,
                  '1' => 56
                )
              ),

              '20' => Array
              (
                '0' => Array
                (
                  '0' => 41,
                  '1' => 56
                )
              ),

              '21' => Array
              (
                '0' => Array
                (
                  '0' => 41,
                  '1' => 56
                )
              ),

              '22' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 56
                )
              ),

              '23' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 56
                )
              ),

              '24' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 56
                )
              ),

              '25' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 56
                )
              ),

              '26' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 56
                )
              ),

              '27' => Array
              (
                '0' => Array
                (
                  '0' => 36,
                  '1' => 56
                )
              ),

              '28' => Array
              (
                '0' => Array
                (
                  '0' => 35,
                  '1' => 56
                )
              ),

              '29' => Array
              (
                '0' => Array
                (
                  '0' => 35,
                  '1' => 56
                )
              ),

              '30' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 55
                )
              ),

              '31' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 55
                )
              ),

              '32' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 55
                )
              ),

              '33' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 55
                )
              ),

              '34' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 55
                )
              ),

              '35' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 55
                )
              ),

              '36' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 54
                )
              ),

              '37' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 54
                )
              ),

              '38' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 54
                )
              ),

              '39' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 54
                )
              ),

              '40' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 54
                )
              ),

              '41' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 54
                )
              ),

              '42' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 39
                ),
                '1' => Array
                (
                  '0' => 41,
                  '1' => 54
                )
              ),

              '43' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 37
                ),
                '1' => Array
                (
                  '0' => 41,
                  '1' => 53
                )
              ),

              '44' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 35
                ),
                '1' => Array
                (
                  '0' => 41,
                  '1' => 53
                )
              ),

              '45' => Array
              (
                '0' => Array
                (
                  '0' => 41,
                  '1' => 53
                )
              ),

              '46' => Array
              (
                '0' => Array
                (
                  '0' => 41,
                  '1' => 53
                )
              ),

              '47' => Array
              (
                '0' => Array
                (
                  '0' => 41,
                  '1' => 53
                )
              ),

              '48' => Array
              (
                '0' => Array
                (
                  '0' => 41,
                  '1' => 53
                )
              ),

              '49' => Array
              (
                '0' => Array
                (
                  '0' => 41,
                  '1' => 53
                )
              ),

              '50' => Array
              (
                '0' => Array
                (
                  '0' => 41,
                  '1' => 53
                )
              ),

              '51' => Array
              (
                '0' => Array
                (
                  '0' => 41,
                  '1' => 53
                )
              ),

              '52' => Array
              (
                '0' => Array
                (
                  '0' => 41,
                  '1' => 53
                )
              ),

              '53' => Array
              (
                '0' => Array
                (
                  '0' => 41,
                  '1' => 53
                )
              ),

              '54' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 53
                )
              ),

              '55' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 53
                )
              ),

              '56' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 52
                )
              ),

              '57' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 52
                )
              ),

              '58' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 52
                )
              ),

              '59' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 52
                )
              ),

              '60' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 52
                )
              ),

              '61' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 52
                )
              ),

              '62' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 52
                )
              ),

              '63' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 52
                )
              ),

              '64' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 51
                )
              ),

              '65' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 51
                )
              ),

              '66' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 51
                )
              ),

              '67' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 51
                )
              ),

              '68' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 51
                )
              ),

              '69' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 51
                )
              ),

              '70' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 50
                )
              ),

              '71' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 50
                )
              ),

              '72' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 50
                )
              ),

              '73' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 50
                )
              ),

              '74' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 50
                )
              ),

              '75' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 49
                )
              ),

              '76' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 49
                )
              ),

              '77' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 49
                )
              ),

              '78' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 49
                )
              ),

              '79' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 49
                ),
                '1' => Array
                (
                  '0' => 68,
                  '1' => 73
                )
              ),

              '80' => Array
              (
                '0' => Array
                (
                  '0' => 17,
                  '1' => 75
                )
              ),

              '81' => Array
              (
                '0' => Array
                (
                  '0' => 16,
                  '1' => 76
                )
              ),

              '82' => Array
              (
                '0' => Array
                (
                  '0' => 15,
                  '1' => 76
                )
              ),

              '83' => Array
              (
                '0' => Array
                (
                  '0' => 14,
                  '1' => 77
                )
              ),

              '84' => Array
              (
                '0' => Array
                (
                  '0' => 13,
                  '1' => 77
                )
              ),

              '85' => Array
              (
                '0' => Array
                (
                  '0' => 13,
                  '1' => 77
                )
              ),

              '86' => Array
              (
                '0' => Array
                (
                  '0' => 13,
                  '1' => 77
                )
              ),

              '87' => Array
              (
                '0' => Array
                (
                  '0' => 13,
                  '1' => 77
                )
              ),

              '88' => Array
              (
                '0' => Array
                (
                  '0' => 13,
                  '1' => 77
                )
              ),

              '89' => Array
              (
                '0' => Array
                (
                  '0' => 14,
                  '1' => 76
                )
              ),

              '90' => Array
              (
                '0' => Array
                (
                  '0' => 15,
                  '1' => 76
                )
              ),

              '91' => Array
              (
                '0' => Array
                (
                  '0' => 16,
                  '1' => 75
                )
              ),

              '92' => Array
              (
                '0' => Array
                (
                  '0' => 17,
                  '1' => 73
                )
              )
            );

            $char_2 = array(
              '8' => Array
              (
                '0' => Array
                (
                  '0' => 46,
                  '1' => 56
                )
              ),

              '9' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 62
                )
              ),

              '10' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 65
                )
              ),

              '11' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 67
                )
              ),

              '12' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 69
                )
              ),

              '13' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 70
                )
              ),

              '14' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 71
                )
              ),

              '15' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 72
                )
              ),

              '16' => Array
              (
                '0' => Array
                (
                  '0' => 26,
                  '1' => 72
                )
              ),

              '17' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 73
                )
              ),

              '18' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 73
                )
              ),

              '19' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 73
                )
              ),

              '20' => Array
              (
                '0' => Array
                (
                  '0' => 22,
                  '1' => 74
                )
              ),

              '21' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 46
                ),
                '1' => Array
                (
                  '0' => 56,
                  '1' => 74
                )
              ),

              '22' => Array
              (
                '0' => Array
                (
                  '0' => 20,
                  '1' => 41
                ),
                '1' => Array
                (
                  '0' => 61,
                  '1' => 74
                )
              ),

              '23' => Array
              (
                '0' => Array
                (
                  '0' => 20,
                  '1' => 39
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '24' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 36
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '25' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 34
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '26' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 33
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 75
                )
              ),

              '27' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 33
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 75
                )
              ),

              '28' => Array
              (
                '0' => Array
                (
                  '0' => 17,
                  '1' => 32
                ),
                '1' => Array
                (
                  '0' => 63,
                  '1' => 75
                )
              ),

              '29' => Array
              (
                '0' => Array
                (
                  '0' => 17,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 63,
                  '1' => 75
                )
              ),

              '30' => Array
              (
                '0' => Array
                (
                  '0' => 17,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 63,
                  '1' => 75
                )
              ),

              '31' => Array
              (
                '0' => Array
                (
                  '0' => 17,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 63,
                  '1' => 75
                )
              ),

              '32' => Array
              (
                '0' => Array
                (
                  '0' => 17,
                  '1' => 29
                ),
                '1' => Array
                (
                  '0' => 63,
                  '1' => 75
                )
              ),

              '33' => Array
              (
                '0' => Array
                (
                  '0' => 17,
                  '1' => 29
                ),
                '1' => Array
                (
                  '0' => 63,
                  '1' => 75
                )
              ),

              '34' => Array
              (
                '0' => Array
                (
                  '0' => 17,
                  '1' => 29
                ),
                '1' => Array
                (
                  '0' => 63,
                  '1' => 75
                )
              ),

              '35' => Array
              (
                '0' => Array
                (
                  '0' => 17,
                  '1' => 29
                ),
                '1' => Array
                (
                  '0' => 63,
                  '1' => 75
                )
              ),

              '36' => Array
              (
                '0' => Array
                (
                  '0' => 17,
                  '1' => 29
                ),
                '1' => Array
                (
                  '0' => 63,
                  '1' => 75
                )
              ),

              '37' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 28
                ),
                '1' => Array
                (
                  '0' => 63,
                  '1' => 75
                )
              ),

              '38' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 27
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 75
                )
              ),

              '39' => Array
              (
                '0' => Array
                (
                  '0' => 20,
                  '1' => 26
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '40' => Array
              (
                '0' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '41' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 74
                )
              ),

              '42' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 74
                )
              ),

              '43' => Array
              (
                '0' => Array
                (
                  '0' => 60,
                  '1' => 73
                )
              ),

              '44' => Array
              (
                '0' => Array
                (
                  '0' => 60,
                  '1' => 73
                )
              ),

              '45' => Array
              (
                '0' => Array
                (
                  '0' => 59,
                  '1' => 73
                )
              ),

              '46' => Array
              (
                '0' => Array
                (
                  '0' => 59,
                  '1' => 72
                )
              ),

              '47' => Array
              (
                '0' => Array
                (
                  '0' => 58,
                  '1' => 72
                )
              ),

              '48' => Array
              (
                '0' => Array
                (
                  '0' => 57,
                  '1' => 71
                )
              ),

              '49' => Array
              (
                '0' => Array
                (
                  '0' => 57,
                  '1' => 71
                )
              ),

              '50' => Array
              (
                '0' => Array
                (
                  '0' => 57,
                  '1' => 70
                )
              ),

              '51' => Array
              (
                '0' => Array
                (
                  '0' => 56,
                  '1' => 70
                )
              ),

              '52' => Array
              (
                '0' => Array
                (
                  '0' => 56,
                  '1' => 69
                )
              ),

              '53' => Array
              (
                '0' => Array
                (
                  '0' => 55,
                  '1' => 69
                )
              ),

              '54' => Array
              (
                '0' => Array
                (
                  '0' => 54,
                  '1' => 68
                )
              ),

              '55' => Array
              (
                '0' => Array
                (
                  '0' => 54,
                  '1' => 68
                )
              ),

              '56' => Array
              (
                '0' => Array
                (
                  '0' => 53,
                  '1' => 68
                )
              ),

              '57' => Array
              (
                '0' => Array
                (
                  '0' => 52,
                  '1' => 67
                )
              ),

              '58' => Array
              (
                '0' => Array
                (
                  '0' => 52,
                  '1' => 67
                )
              ),

              '59' => Array
              (
                '0' => Array
                (
                  '0' => 51,
                  '1' => 66
                )
              ),

              '60' => Array
              (
                '0' => Array
                (
                  '0' => 51,
                  '1' => 65
                )
              ),

              '61' => Array
              (
                '0' => Array
                (
                  '0' => 50,
                  '1' => 65
                )
              ),

              '62' => Array
              (
                '0' => Array
                (
                  '0' => 49,
                  '1' => 64
                )
              ),

              '63' => Array
              (
                '0' => Array
                (
                  '0' => 48,
                  '1' => 63
                )
              ),

              '64' => Array
              (
                '0' => Array
                (
                  '0' => 47,
                  '1' => 63
                )
              ),

              '65' => Array
              (
                '0' => Array
                (
                  '0' => 47,
                  '1' => 62
                )
              ),

              '66' => Array
              (
                '0' => Array
                (
                  '0' => 46,
                  '1' => 62
                )
              ),

              '67' => Array
              (
                '0' => Array
                (
                  '0' => 45,
                  '1' => 61
                )
              ),

              '68' => Array
              (
                '0' => Array
                (
                  '0' => 45,
                  '1' => 60
                )
              ),

              '69' => Array
              (
                '0' => Array
                (
                  '0' => 44,
                  '1' => 59
                )
              ),

              '70' => Array
              (
                '0' => Array
                (
                  '0' => 43,
                  '1' => 59
                )
              ),

              '71' => Array
              (
                '0' => Array
                (
                  '0' => 42,
                  '1' => 62
                )
              ),

              '72' => Array
              (
                '0' => Array
                (
                  '0' => 42,
                  '1' => 64
                ),
                '1' => Array
                (
                  '0' => 83,
                  '1' => 87
                )
              ),

              '73' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 66
                ),
                '1' => Array
                (
                  '0' => 82,
                  '1' => 88
                )
              ),

              '74' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 67
                ),
                '1' => Array
                (
                  '0' => 81,
                  '1' => 89
                )
              ),

              '75' => Array
              (
                '0' => Array
                (
                  '0' => 36,
                  '1' => 68
                ),
                '1' => Array
                (
                  '0' => 80,
                  '1' => 90
                )
              ),

              '76' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 69
                ),
                '1' => Array
                (
                  '0' => 79,
                  '1' => 91
                )
              ),

              '77' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 70
                ),
                '1' => Array
                (
                  '0' => 79,
                  '1' => 91
                )
              ),

              '78' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 70
                ),
                '1' => Array
                (
                  '0' => 79,
                  '1' => 91
                )
              ),

              '79' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 71
                ),
                '1' => Array
                (
                  '0' => 78,
                  '1' => 91
                )
              ),

              '80' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 71
                ),
                '1' => Array
                (
                  '0' => 78,
                  '1' => 91
                )
              ),

              '81' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 72
                ),
                '1' => Array
                (
                  '0' => 77,
                  '1' => 91
                )
              ),

              '82' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 90
                )
              ),

              '83' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 90
                )
              ),

              '84' => Array
              (
                '0' => Array
                (
                  '0' => 26,
                  '1' => 50
                ),
                '1' => Array
                (
                  '0' => 59,
                  '1' => 89
                )
              ),

              '85' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 49
                ),
                '1' => Array
                (
                  '0' => 60,
                  '1' => 89
                )
              ),

              '86' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 48
                ),
                '1' => Array
                (
                  '0' => 61,
                  '1' => 88
                )
              ),

              '87' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 47
                ),
                '1' => Array
                (
                  '0' => 61,
                  '1' => 88
                )
              ),

              '88' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 46
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 88
                )
              ),

              '89' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 44
                ),
                '1' => Array
                (
                  '0' => 63,
                  '1' => 87
                )
              ),

              '90' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 43
                ),
                '1' => Array
                (
                  '0' => 63,
                  '1' => 86
                )
              ),

              '91' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 64,
                  '1' => 86
                )
              ),

              '92' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 39
                ),
                '1' => Array
                (
                  '0' => 66,
                  '1' => 85
                )
              ),

              '93' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 38
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 83
                )
              ),

              '94' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 37
                ),
                '1' => Array
                (
                  '0' => 69,
                  '1' => 82
                )
              ),

              '95' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 36
                ),
                '1' => Array
                (
                  '0' => 75,
                  '1' => 79
                )
              ),

              '96' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 34
                )
              ),

              '97' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 33
                )
              ),

              '98' => Array
              (
                '0' => Array
                (
                  '0' => 26,
                  '1' => 32
                )
              )
            );

            $char_3 = array(
              '4' => Array
              (
                '0' => Array
                (
                  '0' => 49,
                  '1' => 56
                )
              ),

              '5' => Array
              (
                '0' => Array
                (
                  '0' => 45,
                  '1' => 59
                )
              ),

              '6' => Array
              (
                '0' => Array
                (
                  '0' => 42,
                  '1' => 61
                )
              ),

              '7' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 62
                )
              ),

              '8' => Array
              (
                '0' => Array
                (
                  '0' => 35,
                  '1' => 65
                )
              ),

              '9' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 67
                )
              ),

              '10' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 68
                )
              ),

              '11' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 69
                )
              ),

              '12' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 69
                )
              ),

              '13' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 70
                )
              ),

              '14' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 71
                )
              ),

              '15' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 71
                )
              ),

              '16' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 71
                )
              ),

              '17' => Array
              (
                '0' => Array
                (
                  '0' => 22,
                  '1' => 49
                ),
                '1' => Array
                (
                  '0' => 55,
                  '1' => 72
                )
              ),

              '18' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 46
                ),
                '1' => Array
                (
                  '0' => 56,
                  '1' => 72
                )
              ),

              '19' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 43
                ),
                '1' => Array
                (
                  '0' => 58,
                  '1' => 72
                )
              ),

              '20' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 39
                ),
                '1' => Array
                (
                  '0' => 60,
                  '1' => 72
                )
              ),

              '21' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 38
                ),
                '1' => Array
                (
                  '0' => 60,
                  '1' => 72
                )
              ),

              '22' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 36
                ),
                '1' => Array
                (
                  '0' => 60,
                  '1' => 72
                )
              ),

              '23' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 34
                ),
                '1' => Array
                (
                  '0' => 60,
                  '1' => 72
                )
              ),

              '24' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 33
                ),
                '1' => Array
                (
                  '0' => 59,
                  '1' => 72
                )
              ),

              '25' => Array
              (
                '0' => Array
                (
                  '0' => 22,
                  '1' => 32
                ),
                '1' => Array
                (
                  '0' => 59,
                  '1' => 72
                )
              ),

              '26' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 59,
                  '1' => 72
                )
              ),

              '27' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 58,
                  '1' => 72
                )
              ),

              '28' => Array
              (
                '0' => Array
                (
                  '0' => 58,
                  '1' => 71
                )
              ),

              '29' => Array
              (
                '0' => Array
                (
                  '0' => 57,
                  '1' => 71
                )
              ),

              '30' => Array
              (
                '0' => Array
                (
                  '0' => 57,
                  '1' => 70
                )
              ),

              '31' => Array
              (
                '0' => Array
                (
                  '0' => 56,
                  '1' => 70
                )
              ),

              '32' => Array
              (
                '0' => Array
                (
                  '0' => 56,
                  '1' => 70
                )
              ),

              '33' => Array
              (
                '0' => Array
                (
                  '0' => 55,
                  '1' => 69
                )
              ),

              '34' => Array
              (
                '0' => Array
                (
                  '0' => 54,
                  '1' => 69
                )
              ),

              '35' => Array
              (
                '0' => Array
                (
                  '0' => 52,
                  '1' => 68
                )
              ),

              '36' => Array
              (
                '0' => Array
                (
                  '0' => 51,
                  '1' => 67
                )
              ),

              '37' => Array
              (
                '0' => Array
                (
                  '0' => 50,
                  '1' => 67
                )
              ),

              '38' => Array
              (
                '0' => Array
                (
                  '0' => 49,
                  '1' => 66
                )
              ),

              '39' => Array
              (
                '0' => Array
                (
                  '0' => 47,
                  '1' => 66
                )
              ),

              '40' => Array
              (
                '0' => Array
                (
                  '0' => 46,
                  '1' => 65
                )
              ),

              '41' => Array
              (
                '0' => Array
                (
                  '0' => 43,
                  '1' => 64
                )
              ),

              '42' => Array
              (
                '0' => Array
                (
                  '0' => 41,
                  '1' => 63
                )
              ),

              '43' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 63
                )
              ),

              '44' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 61
                )
              ),

              '45' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 61
                )
              ),

              '46' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 63
                )
              ),

              '47' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 65
                )
              ),

              '48' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 66
                )
              ),

              '49' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 68
                )
              ),

              '50' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 69
                )
              ),

              '51' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 70
                )
              ),

              '52' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 71
                )
              ),

              '53' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 71
                )
              ),

              '54' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 72
                )
              ),

              '55' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 72
                )
              ),

              '56' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 73
                )
              ),

              '57' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 74
                )
              ),

              '58' => Array
              (
                '0' => Array
                (
                  '0' => 59,
                  '1' => 74
                )
              ),

              '59' => Array
              (
                '0' => Array
                (
                  '0' => 60,
                  '1' => 75
                )
              ),

              '60' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 75
                )
              ),

              '61' => Array
              (
                '0' => Array
                (
                  '0' => 62,
                  '1' => 75
                )
              ),

              '62' => Array
              (
                '0' => Array
                (
                  '0' => 62,
                  '1' => 76
                )
              ),

              '63' => Array
              (
                '0' => Array
                (
                  '0' => 63,
                  '1' => 76
                )
              ),

              '64' => Array
              (
                '0' => Array
                (
                  '0' => 64,
                  '1' => 76
                )
              ),

              '65' => Array
              (
                '0' => Array
                (
                  '0' => 64,
                  '1' => 76
                )
              ),

              '66' => Array
              (
                '0' => Array
                (
                  '0' => 64,
                  '1' => 76
                )
              ),

              '67' => Array
              (
                '0' => Array
                (
                  '0' => 64,
                  '1' => 76
                )
              ),

              '68' => Array
              (
                '0' => Array
                (
                  '0' => 64,
                  '1' => 76
                )
              ),

              '69' => Array
              (
                '0' => Array
                (
                  '0' => 22,
                  '1' => 26
                ),
                '1' => Array
                (
                  '0' => 64,
                  '1' => 76
                )
              ),

              '70' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 27
                ),
                '1' => Array
                (
                  '0' => 63,
                  '1' => 76
                )
              ),

              '71' => Array
              (
                '0' => Array
                (
                  '0' => 20,
                  '1' => 28
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 76
                )
              ),

              '72' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 29
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 76
                )
              ),

              '73' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 61,
                  '1' => 75
                )
              ),

              '74' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 60,
                  '1' => 75
                )
              ),

              '75' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 59,
                  '1' => 74
                )
              ),

              '76' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 58,
                  '1' => 74
                )
              ),

              '77' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 58,
                  '1' => 73
                )
              ),

              '78' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 57,
                  '1' => 72
                )
              ),

              '79' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 32
                ),
                '1' => Array
                (
                  '0' => 56,
                  '1' => 72
                )
              ),

              '80' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 34
                ),
                '1' => Array
                (
                  '0' => 55,
                  '1' => 71
                )
              ),

              '81' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 35
                ),
                '1' => Array
                (
                  '0' => 53,
                  '1' => 70
                )
              ),

              '82' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 36
                ),
                '1' => Array
                (
                  '0' => 51,
                  '1' => 69
                )
              ),

              '83' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 37
                ),
                '1' => Array
                (
                  '0' => 48,
                  '1' => 68
                )
              ),

              '84' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 68
                )
              ),

              '85' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 67
                )
              ),

              '86' => Array
              (
                '0' => Array
                (
                  '0' => 20,
                  '1' => 66
                )
              ),

              '87' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 66
                )
              ),

              '88' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 65
                )
              ),

              '89' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 64
                )
              ),

              '90' => Array
              (
                '0' => Array
                (
                  '0' => 26,
                  '1' => 63
                )
              ),

              '91' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 62
                )
              ),

              '92' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 61
                )
              ),

              '93' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 59
                )
              ),

              '94' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 55
                )
              ),

              '95' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 52
                )
              ),

              '96' => Array
              (
                '0' => Array
                (
                  '0' => 36,
                  '1' => 49
                )
              )
              );

              $char_4 = array(
              '7' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 40
                )
              ),

              '8' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 41
                )
              ),

              '9' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 42
                )
              ),

              '10' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 43
                )
              ),

              '11' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 43
                )
              ),

              '12' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 43
                )
              ),

              '13' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 43
                )
              ),

              '14' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 43
                )
              ),

              '15' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 43
                )
              ),

              '16' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 43
                )
              ),

              '17' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 43
                )
              ),

              '18' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 43
                )
              ),

              '19' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 42
                )
              ),

              '20' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 42
                )
              ),

              '21' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 42
                )
              ),

              '22' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 41
                )
              ),

              '23' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 41
                ),
                '1' => Array
                (
                  '0' => 71,
                  '1' => 75
                )
              ),

              '24' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 41
                ),
                '1' => Array
                (
                  '0' => 70,
                  '1' => 76
                )
              ),

              '25' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 41
                ),
                '1' => Array
                (
                  '0' => 69,
                  '1' => 77
                )
              ),

              '26' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 41
                ),
                '1' => Array
                (
                  '0' => 68,
                  '1' => 78
                )
              ),

              '27' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 41
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 79
                )
              ),

              '28' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 79
                )
              ),

              '29' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 79
                )
              ),

              '30' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 79
                )
              ),

              '31' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 79
                )
              ),

              '32' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 79
                )
              ),

              '33' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 79
                )
              ),

              '34' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 79
                )
              ),

              '35' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 79
                )
              ),

              '36' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 79
                )
              ),

              '37' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 39
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 79
                )
              ),

              '38' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 39
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 79
                )
              ),

              '39' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 39
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 79
                )
              ),

              '40' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 39
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 79
                )
              ),

              '41' => Array
              (
                '0' => Array
                (
                  '0' => 26,
                  '1' => 39
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 79
                )
              ),

              '42' => Array
              (
                '0' => Array
                (
                  '0' => 26,
                  '1' => 39
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 79
                )
              ),

              '43' => Array
              (
                '0' => Array
                (
                  '0' => 26,
                  '1' => 38
                ),
                '1' => Array
                (
                  '0' => 66,
                  '1' => 79
                )
              ),

              '44' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 38
                ),
                '1' => Array
                (
                  '0' => 66,
                  '1' => 79
                )
              ),

              '45' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 78
                )
              ),

              '46' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 78
                )
              ),

              '47' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 78
                )
              ),

              '48' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 78
                )
              ),

              '49' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 78
                )
              ),

              '50' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 78
                )
              ),

              '51' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 77
                )
              ),

              '52' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 77
                )
              ),

              '53' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 77
                )
              ),

              '54' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 77
                )
              ),

              '55' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 77
                )
              ),

              '56' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 76
                )
              ),

              '57' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 76
                )
              ),

              '58' => Array
              (
                '0' => Array
                (
                  '0' => 63,
                  '1' => 76
                )
              ),

              '59' => Array
              (
                '0' => Array
                (
                  '0' => 63,
                  '1' => 76
                )
              ),

              '60' => Array
              (
                '0' => Array
                (
                  '0' => 63,
                  '1' => 75
                )
              ),

              '61' => Array
              (
                '0' => Array
                (
                  '0' => 62,
                  '1' => 75
                )
              ),

              '62' => Array
              (
                '0' => Array
                (
                  '0' => 62,
                  '1' => 75
                )
              ),

              '63' => Array
              (
                '0' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '64' => Array
              (
                '0' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '65' => Array
              (
                '0' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '66' => Array
              (
                '0' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '67' => Array
              (
                '0' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '68' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 74
                )
              ),

              '69' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '70' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '71' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '72' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '73' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '74' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '75' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '76' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '77' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '78' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '79' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '80' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '81' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '82' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '83' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '84' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '85' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '86' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '87' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '88' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '89' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '90' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 73
                )
              ),

              '91' => Array
              (
                '0' => Array
                (
                  '0' => 62,
                  '1' => 72
                )
              ),

              '92' => Array
              (
                '0' => Array
                (
                  '0' => 63,
                  '1' => 71
                )
              ),

              '93' => Array
              (
                '0' => Array
                (
                  '0' => 64,
                  '1' => 70
                )
              ),

              '94' => Array
              (
                '0' => Array
                (
                  '0' => 65,
                  '1' => 69
                )
              )
            );

            $char_5 = array(
              '6' => Array
              (
                '0' => Array
                (
                  '0' => 84,
                  '1' => 90
                )
              ),

              '7' => Array
              (
                '0' => Array
                (
                  '0' => 83,
                  '1' => 91
                )
              ),

              '8' => Array
              (
                '0' => Array
                (
                  '0' => 46,
                  '1' => 53
                ),
                '1' => Array
                (
                  '0' => 82,
                  '1' => 92
                )
              ),

              '9' => Array
              (
                '0' => Array
                (
                  '0' => 45,
                  '1' => 55
                ),
                '1' => Array
                (
                  '0' => 81,
                  '1' => 93
                )
              ),

              '10' => Array
              (
                '0' => Array
                (
                  '0' => 44,
                  '1' => 57
                ),
                '1' => Array
                (
                  '0' => 80,
                  '1' => 93
                )
              ),

              '11' => Array
              (
                '0' => Array
                (
                  '0' => 43,
                  '1' => 59
                ),
                '1' => Array
                (
                  '0' => 76,
                  '1' => 93
                )
              ),

              '12' => Array
              (
                '0' => Array
                (
                  '0' => 43,
                  '1' => 93
                )
              ),

              '13' => Array
              (
                '0' => Array
                (
                  '0' => 42,
                  '1' => 93
                )
              ),

              '14' => Array
              (
                '0' => Array
                (
                  '0' => 41,
                  '1' => 93
                )
              ),

              '15' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 93
                )
              ),

              '16' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 92
                )
              ),

              '17' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 92
                )
              ),

              '18' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 91
                )
              ),

              '19' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 90
                )
              ),

              '20' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 88
                )
              ),

              '21' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 87
                )
              ),

              '22' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 85
                )
              ),

              '23' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 52
                ),
                '1' => Array
                (
                  '0' => 54,
                  '1' => 83
                )
              ),

              '24' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 52
                ),
                '1' => Array
                (
                  '0' => 57,
                  '1' => 77
                )
              ),

              '25' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 52
                )
              ),

              '26' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 52
                )
              ),

              '27' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 52
                )
              ),

              '28' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 52
                )
              ),

              '29' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 52
                )
              ),

              '30' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 52
                )
              ),

              '31' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 52
                )
              ),

              '32' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 52
                )
              ),

              '33' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 52
                )
              ),

              '34' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 52
                )
              ),

              '35' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 52
                )
              ),

              '36' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 52
                )
              ),

              '37' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 51
                )
              ),

              '38' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 51
                )
              ),

              '39' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 51
                )
              ),

              '40' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 50
                )
              ),

              '41' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 50
                )
              ),

              '42' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 50
                )
              ),

              '43' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 51
                )
              ),

              '44' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 54
                )
              ),

              '45' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 57
                )
              ),

              '46' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 59
                )
              ),

              '47' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 61
                )
              ),

              '48' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 62
                )
              ),

              '49' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 64
                )
              ),

              '50' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 65
                )
              ),

              '51' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 67
                )
              ),

              '52' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 68
                )
              ),

              '53' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 69
                )
              ),

              '54' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 69
                )
              ),

              '55' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 70
                )
              ),

              '56' => Array
              (
                '0' => Array
                (
                  '0' => 50,
                  '1' => 71
                )
              ),

              '57' => Array
              (
                '0' => Array
                (
                  '0' => 53,
                  '1' => 71
                )
              ),

              '58' => Array
              (
                '0' => Array
                (
                  '0' => 17,
                  '1' => 19
                ),
                '1' => Array
                (
                  '0' => 54,
                  '1' => 72
                )
              ),

              '59' => Array
              (
                '0' => Array
                (
                  '0' => 15,
                  '1' => 21
                ),
                '1' => Array
                (
                  '0' => 56,
                  '1' => 73
                )
              ),

              '60' => Array
              (
                '0' => Array
                (
                  '0' => 14,
                  '1' => 22
                ),
                '1' => Array
                (
                  '0' => 58,
                  '1' => 73
                )
              ),

              '61' => Array
              (
                '0' => Array
                (
                  '0' => 13,
                  '1' => 23
                ),
                '1' => Array
                (
                  '0' => 59,
                  '1' => 73
                )
              ),

              '62' => Array
              (
                '0' => Array
                (
                  '0' => 13,
                  '1' => 23
                ),
                '1' => Array
                (
                  '0' => 60,
                  '1' => 74
                )
              ),

              '63' => Array
              (
                '0' => Array
                (
                  '0' => 12,
                  '1' => 24
                ),
                '1' => Array
                (
                  '0' => 60,
                  '1' => 74
                )
              ),

              '64' => Array
              (
                '0' => Array
                (
                  '0' => 12,
                  '1' => 24
                ),
                '1' => Array
                (
                  '0' => 61,
                  '1' => 74
                )
              ),

              '65' => Array
              (
                '0' => Array
                (
                  '0' => 12,
                  '1' => 24
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '66' => Array
              (
                '0' => Array
                (
                  '0' => 12,
                  '1' => 24
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '67' => Array
              (
                '0' => Array
                (
                  '0' => 12,
                  '1' => 24
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '68' => Array
              (
                '0' => Array
                (
                  '0' => 12,
                  '1' => 24
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '69' => Array
              (
                '0' => Array
                (
                  '0' => 12,
                  '1' => 25
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '70' => Array
              (
                '0' => Array
                (
                  '0' => 13,
                  '1' => 25
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '71' => Array
              (
                '0' => Array
                (
                  '0' => 13,
                  '1' => 26
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '72' => Array
              (
                '0' => Array
                (
                  '0' => 13,
                  '1' => 26
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '73' => Array
              (
                '0' => Array
                (
                  '0' => 14,
                  '1' => 27
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '74' => Array
              (
                '0' => Array
                (
                  '0' => 14,
                  '1' => 28
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '75' => Array
              (
                '0' => Array
                (
                  '0' => 14,
                  '1' => 28
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '76' => Array
              (
                '0' => Array
                (
                  '0' => 15,
                  '1' => 29
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '77' => Array
              (
                '0' => Array
                (
                  '0' => 15,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '78' => Array
              (
                '0' => Array
                (
                  '0' => 15,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 74
                )
              ),

              '79' => Array
              (
                '0' => Array
                (
                  '0' => 16,
                  '1' => 33
                ),
                '1' => Array
                (
                  '0' => 61,
                  '1' => 74
                )
              ),

              '80' => Array
              (
                '0' => Array
                (
                  '0' => 17,
                  '1' => 34
                ),
                '1' => Array
                (
                  '0' => 61,
                  '1' => 74
                )
              ),

              '81' => Array
              (
                '0' => Array
                (
                  '0' => 17,
                  '1' => 35
                ),
                '1' => Array
                (
                  '0' => 60,
                  '1' => 74
                )
              ),

              '82' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 36
                ),
                '1' => Array
                (
                  '0' => 58,
                  '1' => 74
                )
              ),

              '83' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 38
                ),
                '1' => Array
                (
                  '0' => 53,
                  '1' => 73
                )
              ),

              '84' => Array
              (
                '0' => Array
                (
                  '0' => 20,
                  '1' => 39
                ),
                '1' => Array
                (
                  '0' => 49,
                  '1' => 73
                )
              ),

              '85' => Array
              (
                '0' => Array
                (
                  '0' => 20,
                  '1' => 72
                )
              ),

              '86' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 71
                )
              ),

              '87' => Array
              (
                '0' => Array
                (
                  '0' => 22,
                  '1' => 71
                )
              ),

              '88' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 70
                )
              ),

              '89' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 70
                )
              ),

              '90' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 69
                )
              ),

              '91' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 68
                )
              ),

              '92' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 66
                )
              ),

              '93' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 65
                )
              ),

              '94' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 63
                )
              ),

              '95' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 60
                )
              ),

              '96' => Array
              (
                '0' => Array
                (
                  '0' => 35,
                  '1' => 55
                )
              ),

              '97' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 49
                )
              )
            );

            $char_6 = array(
              '9' => Array
              (
                '0' => Array
                (
                  '0' => 50,
                  '1' => 63
                )
              ),

              '10' => Array
              (
                '0' => Array
                (
                  '0' => 47,
                  '1' => 66
                )
              ),

              '11' => Array
              (
                '0' => Array
                (
                  '0' => 46,
                  '1' => 68
                )
              ),

              '12' => Array
              (
                '0' => Array
                (
                  '0' => 45,
                  '1' => 70
                )
              ),

              '13' => Array
              (
                '0' => Array
                (
                  '0' => 44,
                  '1' => 72
                )
              ),

              '14' => Array
              (
                '0' => Array
                (
                  '0' => 43,
                  '1' => 73
                )
              ),

              '15' => Array
              (
                '0' => Array
                (
                  '0' => 43,
                  '1' => 74
                )
              ),

              '16' => Array
              (
                '0' => Array
                (
                  '0' => 42,
                  '1' => 75
                )
              ),

              '17' => Array
              (
                '0' => Array
                (
                  '0' => 41,
                  '1' => 77
                )
              ),

              '18' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 78
                )
              ),

              '19' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 79
                )
              ),

              '20' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 80
                )
              ),

              '21' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 80
                )
              ),

              '22' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 53
                ),
                '1' => Array
                (
                  '0' => 61,
                  '1' => 80
                )
              ),

              '23' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 52
                ),
                '1' => Array
                (
                  '0' => 64,
                  '1' => 80
                )
              ),

              '24' => Array
              (
                '0' => Array
                (
                  '0' => 36,
                  '1' => 51
                ),
                '1' => Array
                (
                  '0' => 65,
                  '1' => 80
                )
              ),

              '25' => Array
              (
                '0' => Array
                (
                  '0' => 35,
                  '1' => 50
                ),
                '1' => Array
                (
                  '0' => 66,
                  '1' => 80
                )
              ),

              '26' => Array
              (
                '0' => Array
                (
                  '0' => 35,
                  '1' => 50
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 80
                )
              ),

              '27' => Array
              (
                '0' => Array
                (
                  '0' => 35,
                  '1' => 49
                ),
                '1' => Array
                (
                  '0' => 69,
                  '1' => 79
                )
              ),

              '28' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 48
                ),
                '1' => Array
                (
                  '0' => 70,
                  '1' => 78
                )
              ),

              '29' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 48
                ),
                '1' => Array
                (
                  '0' => 71,
                  '1' => 77
                )
              ),

              '30' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 47
                )
              ),

              '31' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 46
                )
              ),

              '32' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 46
                )
              ),

              '33' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 46
                )
              ),

              '34' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 46
                )
              ),

              '35' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 46
                )
              ),

              '36' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 45
                )
              ),

              '37' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 45
                )
              ),

              '38' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 44
                )
              ),

              '39' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 44
                )
              ),

              '40' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 43
                )
              ),

              '41' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 43
                )
              ),

              '42' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 43
                )
              ),

              '43' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 42
                )
              ),

              '44' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 42
                )
              ),

              '45' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 41
                )
              ),

              '46' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 41
                )
              ),

              '47' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 41
                )
              ),

              '48' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 65
                )
              ),

              '49' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 68
                )
              ),

              '50' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 69
                )
              ),

              '51' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 71
                )
              ),

              '52' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 72
                )
              ),

              '53' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 74
                )
              ),

              '54' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 75
                )
              ),

              '55' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 76
                )
              ),

              '56' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 77
                )
              ),

              '57' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 77
                )
              ),

              '58' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 78
                )
              ),

              '59' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 78
                )
              ),

              '60' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 79
                )
              ),

              '61' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 42
                ),
                '1' => Array
                (
                  '0' => 63,
                  '1' => 79
                )
              ),

              '62' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 65,
                  '1' => 80
                )
              ),

              '63' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 66,
                  '1' => 80
                )
              ),

              '64' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 80
                )
              ),

              '65' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 68,
                  '1' => 80
                )
              ),

              '66' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 68,
                  '1' => 80
                )
              ),

              '67' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 68,
                  '1' => 80
                )
              ),

              '68' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 68,
                  '1' => 80
                )
              ),

              '69' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 68,
                  '1' => 80
                )
              ),

              '70' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 68,
                  '1' => 80
                )
              ),

              '71' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 68,
                  '1' => 80
                )
              ),

              '72' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 68,
                  '1' => 80
                )
              ),

              '73' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 80
                )
              ),

              '74' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 41
                ),
                '1' => Array
                (
                  '0' => 67,
                  '1' => 80
                )
              ),

              '75' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 41
                ),
                '1' => Array
                (
                  '0' => 66,
                  '1' => 80
                )
              ),

              '76' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 42
                ),
                '1' => Array
                (
                  '0' => 66,
                  '1' => 79
                )
              ),

              '77' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 43
                ),
                '1' => Array
                (
                  '0' => 65,
                  '1' => 79
                )
              ),

              '78' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 43
                ),
                '1' => Array
                (
                  '0' => 64,
                  '1' => 78
                )
              ),

              '79' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 44
                ),
                '1' => Array
                (
                  '0' => 64,
                  '1' => 78
                )
              ),

              '80' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 45
                ),
                '1' => Array
                (
                  '0' => 63,
                  '1' => 77
                )
              ),

              '81' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 46
                ),
                '1' => Array
                (
                  '0' => 63,
                  '1' => 77
                )
              ),

              '82' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 47
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 76
                )
              ),

              '83' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 49
                ),
                '1' => Array
                (
                  '0' => 60,
                  '1' => 76
                )
              ),

              '84' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 75
                )
              ),

              '85' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 75
                )
              ),

              '86' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 74
                )
              ),

              '87' => Array
              (
                '0' => Array
                (
                  '0' => 35,
                  '1' => 73
                )
              ),

              '88' => Array
              (
                '0' => Array
                (
                  '0' => 36,
                  '1' => 73
                )
              ),

              '89' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 72
                )
              ),

              '90' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 72
                )
              ),

              '91' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 71
                )
              ),

              '92' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 70
                )
              ),

              '93' => Array
              (
                '0' => Array
                (
                  '0' => 41,
                  '1' => 68
                )
              ),

              '94' => Array
              (
                '0' => Array
                (
                  '0' => 43,
                  '1' => 67
                )
              ),

              '95' => Array
              (
                '0' => Array
                (
                  '0' => 45,
                  '1' => 64
                )
              ),

              '96' => Array
              (
                '0' => Array
                (
                  '0' => 48,
                  '1' => 60
                )
              )
            );

            $char_7 = array(
              '9' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 27
                )
              ),

              '10' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 30
                )
              ),

              '11' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 77
                )
              ),

              '12' => Array
              (
                '0' => Array
                (
                  '0' => 22,
                  '1' => 78
                )
              ),

              '13' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 79
                )
              ),

              '14' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 80
                )
              ),

              '15' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 80
                )
              ),

              '16' => Array
              (
                '0' => Array
                (
                  '0' => 20,
                  '1' => 80
                )
              ),

              '17' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 80
                )
              ),

              '18' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 80
                )
              ),

              '19' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 80
                )
              ),

              '20' => Array
              (
                '0' => Array
                (
                  '0' => 22,
                  '1' => 80
                )
              ),

              '21' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 79
                )
              ),

              '22' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 79
                )
              ),

              '23' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 78
                )
              ),

              '24' => Array
              (
                '0' => Array
                (
                  '0' => 64,
                  '1' => 78
                )
              ),

              '25' => Array
              (
                '0' => Array
                (
                  '0' => 63,
                  '1' => 77
                )
              ),

              '26' => Array
              (
                '0' => Array
                (
                  '0' => 63,
                  '1' => 76
                )
              ),

              '27' => Array
              (
                '0' => Array
                (
                  '0' => 62,
                  '1' => 76
                )
              ),

              '28' => Array
              (
                '0' => Array
                (
                  '0' => 62,
                  '1' => 75
                )
              ),

              '29' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 75
                )
              ),

              '30' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 74
                )
              ),

              '31' => Array
              (
                '0' => Array
                (
                  '0' => 60,
                  '1' => 74
                )
              ),

              '32' => Array
              (
                '0' => Array
                (
                  '0' => 60,
                  '1' => 73
                )
              ),

              '33' => Array
              (
                '0' => Array
                (
                  '0' => 60,
                  '1' => 73
                )
              ),

              '34' => Array
              (
                '0' => Array
                (
                  '0' => 59,
                  '1' => 72
                )
              ),

              '35' => Array
              (
                '0' => Array
                (
                  '0' => 59,
                  '1' => 72
                )
              ),

              '36' => Array
              (
                '0' => Array
                (
                  '0' => 59,
                  '1' => 71
                )
              ),

              '37' => Array
              (
                '0' => Array
                (
                  '0' => 58,
                  '1' => 71
                )
              ),

              '38' => Array
              (
                '0' => Array
                (
                  '0' => 58,
                  '1' => 71
                )
              ),

              '39' => Array
              (
                '0' => Array
                (
                  '0' => 57,
                  '1' => 70
                )
              ),

              '40' => Array
              (
                '0' => Array
                (
                  '0' => 57,
                  '1' => 70
                )
              ),

              '41' => Array
              (
                '0' => Array
                (
                  '0' => 56,
                  '1' => 70
                )
              ),

              '42' => Array
              (
                '0' => Array
                (
                  '0' => 56,
                  '1' => 69
                )
              ),

              '43' => Array
              (
                '0' => Array
                (
                  '0' => 55,
                  '1' => 69
                )
              ),

              '44' => Array
              (
                '0' => Array
                (
                  '0' => 54,
                  '1' => 71
                )
              ),

              '45' => Array
              (
                '0' => Array
                (
                  '0' => 51,
                  '1' => 74
                )
              ),

              '46' => Array
              (
                '0' => Array
                (
                  '0' => 48,
                  '1' => 76
                )
              ),

              '47' => Array
              (
                '0' => Array
                (
                  '0' => 42,
                  '1' => 78
                )
              ),

              '48' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 79
                )
              ),

              '49' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 82
                )
              ),

              '50' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 83
                )
              ),

              '51' => Array
              (
                '0' => Array
                (
                  '0' => 36,
                  '1' => 84
                )
              ),

              '52' => Array
              (
                '0' => Array
                (
                  '0' => 35,
                  '1' => 85
                )
              ),

              '53' => Array
              (
                '0' => Array
                (
                  '0' => 35,
                  '1' => 86
                )
              ),

              '54' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 87
                )
              ),

              '55' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 87
                )
              ),

              '56' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 88
                )
              ),

              '57' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 63
                ),
                '1' => Array
                (
                  '0' => 70,
                  '1' => 88
                )
              ),

              '58' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 63
                ),
                '1' => Array
                (
                  '0' => 72,
                  '1' => 88
                )
              ),

              '59' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 62
                ),
                '1' => Array
                (
                  '0' => 74,
                  '1' => 88
                )
              ),

              '60' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 45
                ),
                '1' => Array
                (
                  '0' => 49,
                  '1' => 62
                ),
                '2' => Array
                (
                  '0' => 75,
                  '1' => 88
                )
              ),

              '61' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 44
                ),
                '1' => Array
                (
                  '0' => 49,
                  '1' => 62
                ),
                '2' => Array
                (
                  '0' => 76,
                  '1' => 87
                )
              ),

              '62' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 43
                ),
                '1' => Array
                (
                  '0' => 48,
                  '1' => 61
                ),
                '2' => Array
                (
                  '0' => 77,
                  '1' => 87
                )
              ),

              '63' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 43
                ),
                '1' => Array
                (
                  '0' => 48,
                  '1' => 61
                ),
                '2' => Array
                (
                  '0' => 78,
                  '1' => 86
                )
              ),

              '64' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 42
                ),
                '1' => Array
                (
                  '0' => 48,
                  '1' => 60
                ),
                '2' => Array
                (
                  '0' => 80,
                  '1' => 84
                )
              ),

              '65' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 41
                ),
                '1' => Array
                (
                  '0' => 47,
                  '1' => 60
                )
              ),

              '66' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 47,
                  '1' => 60
                )
              ),

              '67' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 38
                ),
                '1' => Array
                (
                  '0' => 47,
                  '1' => 59
                )
              ),

              '68' => Array
              (
                '0' => Array
                (
                  '0' => 47,
                  '1' => 59
                )
              ),

              '69' => Array
              (
                '0' => Array
                (
                  '0' => 47,
                  '1' => 59
                )
              ),

              '70' => Array
              (
                '0' => Array
                (
                  '0' => 47,
                  '1' => 59
                )
              ),

              '71' => Array
              (
                '0' => Array
                (
                  '0' => 47,
                  '1' => 59
                )
              ),

              '72' => Array
              (
                '0' => Array
                (
                  '0' => 47,
                  '1' => 59
                )
              ),

              '73' => Array
              (
                '0' => Array
                (
                  '0' => 47,
                  '1' => 59
                )
              ),

              '74' => Array
              (
                '0' => Array
                (
                  '0' => 47,
                  '1' => 59
                )
              ),

              '75' => Array
              (
                '0' => Array
                (
                  '0' => 46,
                  '1' => 59
                )
              ),

              '76' => Array
              (
                '0' => Array
                (
                  '0' => 46,
                  '1' => 59
                )
              ),

              '77' => Array
              (
                '0' => Array
                (
                  '0' => 46,
                  '1' => 58
                )
              ),

              '78' => Array
              (
                '0' => Array
                (
                  '0' => 46,
                  '1' => 58
                )
              ),

              '79' => Array
              (
                '0' => Array
                (
                  '0' => 46,
                  '1' => 58
                )
              ),

              '80' => Array
              (
                '0' => Array
                (
                  '0' => 46,
                  '1' => 58
                )
              ),

              '81' => Array
              (
                '0' => Array
                (
                  '0' => 46,
                  '1' => 58
                )
              ),

              '82' => Array
              (
                '0' => Array
                (
                  '0' => 46,
                  '1' => 58
                )
              ),

              '83' => Array
              (
                '0' => Array
                (
                  '0' => 46,
                  '1' => 59
                )
              ),

              '84' => Array
              (
                '0' => Array
                (
                  '0' => 46,
                  '1' => 59
                )
              ),

              '85' => Array
              (
                '0' => Array
                (
                  '0' => 46,
                  '1' => 60
                )
              ),

              '86' => Array
              (
                '0' => Array
                (
                  '0' => 46,
                  '1' => 60
                )
              ),

              '87' => Array
              (
                '0' => Array
                (
                  '0' => 46,
                  '1' => 60
                )
              ),

              '88' => Array
              (
                '0' => Array
                (
                  '0' => 47,
                  '1' => 60
                )
              ),

              '89' => Array
              (
                '0' => Array
                (
                  '0' => 47,
                  '1' => 60
                )
              ),

              '90' => Array
              (
                '0' => Array
                (
                  '0' => 48,
                  '1' => 60
                )
              ),

              '91' => Array
              (
                '0' => Array
                (
                  '0' => 48,
                  '1' => 60
                )
              ),

              '92' => Array
              (
                '0' => Array
                (
                  '0' => 48,
                  '1' => 60
                )
              ),

              '93' => Array
              (
                '0' => Array
                (
                  '0' => 49,
                  '1' => 59
                )
              ),

              '94' => Array
              (
                '0' => Array
                (
                  '0' => 49,
                  '1' => 59
                )
              ),

              '95' => Array
              (
                '0' => Array
                (
                  '0' => 50,
                  '1' => 58
                )
              ),

              '96' => Array
              (
                '0' => Array
                (
                  '0' => 52,
                  '1' => 56
                )
              )
            );

            $char_8 = array(
              '9' => Array
              (
                '0' => Array
                (
                  '0' => 55,
                  '1' => 67
                )
              ),

              '10' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 69
                )
              ),

              '11' => Array
              (
                '0' => Array
                (
                  '0' => 36,
                  '1' => 71
                )
              ),

              '12' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 72
                )
              ),

              '13' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 72
                )
              ),

              '14' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 73
                )
              ),

              '15' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 74
                )
              ),

              '16' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 74
                )
              ),

              '17' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 74
                )
              ),

              '18' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 75
                )
              ),

              '19' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 75
                )
              ),

              '20' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 76
                )
              ),

              '21' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 76
                )
              ),

              '22' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 55
                ),
                '1' => Array
                (
                  '0' => 63,
                  '1' => 76
                )
              ),

              '23' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 43
                ),
                '1' => Array
                (
                  '0' => 64,
                  '1' => 77
                )
              ),

              '24' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 44
                ),
                '1' => Array
                (
                  '0' => 64,
                  '1' => 77
                )
              ),

              '25' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 44
                ),
                '1' => Array
                (
                  '0' => 65,
                  '1' => 77
                )
              ),

              '26' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 45
                ),
                '1' => Array
                (
                  '0' => 65,
                  '1' => 77
                )
              ),

              '27' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 45
                ),
                '1' => Array
                (
                  '0' => 65,
                  '1' => 77
                )
              ),

              '28' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 46
                ),
                '1' => Array
                (
                  '0' => 65,
                  '1' => 77
                )
              ),

              '29' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 48
                ),
                '1' => Array
                (
                  '0' => 65,
                  '1' => 77
                )
              ),

              '30' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 49
                ),
                '1' => Array
                (
                  '0' => 64,
                  '1' => 77
                )
              ),

              '31' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 49
                ),
                '1' => Array
                (
                  '0' => 64,
                  '1' => 77
                )
              ),

              '32' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 50
                ),
                '1' => Array
                (
                  '0' => 63,
                  '1' => 76
                )
              ),

              '33' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 51
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 76
                )
              ),

              '34' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 52
                ),
                '1' => Array
                (
                  '0' => 61,
                  '1' => 76
                )
              ),

              '35' => Array
              (
                '0' => Array
                (
                  '0' => 35,
                  '1' => 53
                ),
                '1' => Array
                (
                  '0' => 60,
                  '1' => 75
                )
              ),

              '36' => Array
              (
                '0' => Array
                (
                  '0' => 36,
                  '1' => 53
                ),
                '1' => Array
                (
                  '0' => 59,
                  '1' => 75
                )
              ),

              '37' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 54
                ),
                '1' => Array
                (
                  '0' => 57,
                  '1' => 74
                )
              ),

              '38' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 74
                )
              ),

              '39' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 73
                )
              ),

              '40' => Array
              (
                '0' => Array
                (
                  '0' => 41,
                  '1' => 72
                )
              ),

              '41' => Array
              (
                '0' => Array
                (
                  '0' => 42,
                  '1' => 71
                )
              ),

              '42' => Array
              (
                '0' => Array
                (
                  '0' => 42,
                  '1' => 70
                )
              ),

              '43' => Array
              (
                '0' => Array
                (
                  '0' => 43,
                  '1' => 69
                )
              ),

              '44' => Array
              (
                '0' => Array
                (
                  '0' => 41,
                  '1' => 68
                )
              ),

              '45' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 68
                )
              ),

              '46' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 66
                )
              ),

              '47' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 65
                )
              ),

              '48' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 63
                )
              ),

              '49' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 63
                )
              ),

              '50' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 64
                )
              ),

              '51' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 64
                )
              ),

              '52' => Array
              (
                '0' => Array
                (
                  '0' => 36,
                  '1' => 65
                )
              ),

              '53' => Array
              (
                '0' => Array
                (
                  '0' => 35,
                  '1' => 65
                )
              ),

              '54' => Array
              (
                '0' => Array
                (
                  '0' => 35,
                  '1' => 49
                ),
                '1' => Array
                (
                  '0' => 52,
                  '1' => 66
                )
              ),

              '55' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 48
                ),
                '1' => Array
                (
                  '0' => 52,
                  '1' => 67
                )
              ),

              '56' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 48
                ),
                '1' => Array
                (
                  '0' => 53,
                  '1' => 67
                )
              ),

              '57' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 47
                ),
                '1' => Array
                (
                  '0' => 54,
                  '1' => 68
                )
              ),

              '58' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 46
                ),
                '1' => Array
                (
                  '0' => 54,
                  '1' => 68
                )
              ),

              '59' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 46
                ),
                '1' => Array
                (
                  '0' => 55,
                  '1' => 69
                )
              ),

              '60' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 45
                ),
                '1' => Array
                (
                  '0' => 55,
                  '1' => 69
                )
              ),

              '61' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 45
                ),
                '1' => Array
                (
                  '0' => 56,
                  '1' => 69
                )
              ),

              '62' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 44
                ),
                '1' => Array
                (
                  '0' => 57,
                  '1' => 70
                )
              ),

              '63' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 44
                ),
                '1' => Array
                (
                  '0' => 57,
                  '1' => 70
                )
              ),

              '64' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 43
                ),
                '1' => Array
                (
                  '0' => 58,
                  '1' => 70
                )
              ),

              '65' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 43
                ),
                '1' => Array
                (
                  '0' => 58,
                  '1' => 71
                )
              ),

              '66' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 43
                ),
                '1' => Array
                (
                  '0' => 58,
                  '1' => 71
                )
              ),

              '67' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 43
                ),
                '1' => Array
                (
                  '0' => 59,
                  '1' => 71
                )
              ),

              '68' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 43
                ),
                '1' => Array
                (
                  '0' => 59,
                  '1' => 71
                )
              ),

              '69' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 43
                ),
                '1' => Array
                (
                  '0' => 59,
                  '1' => 71
                )
              ),

              '70' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 43
                ),
                '1' => Array
                (
                  '0' => 59,
                  '1' => 71
                )
              ),

              '71' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 43
                ),
                '1' => Array
                (
                  '0' => 59,
                  '1' => 71
                )
              ),

              '72' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 43
                ),
                '1' => Array
                (
                  '0' => 59,
                  '1' => 71
                )
              ),

              '73' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 43
                ),
                '1' => Array
                (
                  '0' => 59,
                  '1' => 71
                )
              ),

              '74' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 43
                ),
                '1' => Array
                (
                  '0' => 59,
                  '1' => 71
                )
              ),

              '75' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 44
                ),
                '1' => Array
                (
                  '0' => 59,
                  '1' => 71
                )
              ),

              '76' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 45
                ),
                '1' => Array
                (
                  '0' => 58,
                  '1' => 71
                )
              ),

              '77' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 46
                ),
                '1' => Array
                (
                  '0' => 58,
                  '1' => 71
                )
              ),

              '78' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 47
                ),
                '1' => Array
                (
                  '0' => 57,
                  '1' => 71
                )
              ),

              '79' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 48
                ),
                '1' => Array
                (
                  '0' => 57,
                  '1' => 70
                )
              ),

              '80' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 49
                ),
                '1' => Array
                (
                  '0' => 56,
                  '1' => 70
                )
              ),

              '81' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 69
                )
              ),

              '82' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 69
                )
              ),

              '83' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 68
                )
              ),

              '84' => Array
              (
                '0' => Array
                (
                  '0' => 35,
                  '1' => 68
                )
              ),

              '85' => Array
              (
                '0' => Array
                (
                  '0' => 36,
                  '1' => 67
                )
              ),

              '86' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 67
                )
              ),

              '87' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 66
                )
              ),

              '88' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 66
                )
              ),

              '89' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 65
                )
              ),

              '90' => Array
              (
                '0' => Array
                (
                  '0' => 42,
                  '1' => 64
                )
              ),

              '91' => Array
              (
                '0' => Array
                (
                  '0' => 43,
                  '1' => 63
                )
              ),

              '92' => Array
              (
                '0' => Array
                (
                  '0' => 45,
                  '1' => 60
                )
              ),

              '93' => Array
              (
                '0' => Array
                (
                  '0' => 48,
                  '1' => 57
                )
              )
              );

              $char_9 = array(
              '8' => Array
              (
                '0' => Array
                (
                  '0' => 45,
                  '1' => 52
                )
              ),

              '9' => Array
              (
                '0' => Array
                (
                  '0' => 42,
                  '1' => 60
                )
              ),

              '10' => Array
              (
                '0' => Array
                (
                  '0' => 40,
                  '1' => 65
                )
              ),

              '11' => Array
              (
                '0' => Array
                (
                  '0' => 39,
                  '1' => 69
                )
              ),

              '12' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 72
                )
              ),

              '13' => Array
              (
                '0' => Array
                (
                  '0' => 36,
                  '1' => 73
                )
              ),

              '14' => Array
              (
                '0' => Array
                (
                  '0' => 35,
                  '1' => 74
                )
              ),

              '15' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 75
                )
              ),

              '16' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 75
                )
              ),

              '17' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 75
                )
              ),

              '18' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 76
                )
              ),

              '19' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 77
                )
              ),

              '20' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 78
                )
              ),

              '21' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 46
                ),
                '1' => Array
                (
                  '0' => 52,
                  '1' => 78
                )
              ),

              '22' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 45
                ),
                '1' => Array
                (
                  '0' => 60,
                  '1' => 79
                )
              ),

              '23' => Array
              (
                '0' => Array
                (
                  '0' => 26,
                  '1' => 44
                ),
                '1' => Array
                (
                  '0' => 64,
                  '1' => 79
                )
              ),

              '24' => Array
              (
                '0' => Array
                (
                  '0' => 26,
                  '1' => 43
                ),
                '1' => Array
                (
                  '0' => 66,
                  '1' => 79
                )
              ),

              '25' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 41
                ),
                '1' => Array
                (
                  '0' => 66,
                  '1' => 79
                )
              ),

              '26' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 66,
                  '1' => 79
                )
              ),

              '27' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 39
                ),
                '1' => Array
                (
                  '0' => 66,
                  '1' => 79
                )
              ),

              '28' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 38
                ),
                '1' => Array
                (
                  '0' => 66,
                  '1' => 79
                )
              ),

              '29' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 37
                ),
                '1' => Array
                (
                  '0' => 66,
                  '1' => 79
                )
              ),

              '30' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 36
                ),
                '1' => Array
                (
                  '0' => 66,
                  '1' => 79
                )
              ),

              '31' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 36
                ),
                '1' => Array
                (
                  '0' => 66,
                  '1' => 78
                )
              ),

              '32' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 36
                ),
                '1' => Array
                (
                  '0' => 65,
                  '1' => 78
                )
              ),

              '33' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 36
                ),
                '1' => Array
                (
                  '0' => 65,
                  '1' => 78
                )
              ),

              '34' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 36
                ),
                '1' => Array
                (
                  '0' => 64,
                  '1' => 78
                )
              ),

              '35' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 37
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 78
                )
              ),

              '36' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 39
                ),
                '1' => Array
                (
                  '0' => 60,
                  '1' => 78
                )
              ),

              '37' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 41
                ),
                '1' => Array
                (
                  '0' => 58,
                  '1' => 78
                )
              ),

              '38' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 44
                ),
                '1' => Array
                (
                  '0' => 56,
                  '1' => 78
                )
              ),

              '39' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 47
                ),
                '1' => Array
                (
                  '0' => 54,
                  '1' => 78
                )
              ),

              '40' => Array
              (
                '0' => Array
                (
                  '0' => 26,
                  '1' => 78
                )
              ),

              '41' => Array
              (
                '0' => Array
                (
                  '0' => 26,
                  '1' => 78
                )
              ),

              '42' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 78
                )
              ),

              '43' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 78
                )
              ),

              '44' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 77
                )
              ),

              '45' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 77
                )
              ),

              '46' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 77
                )
              ),

              '47' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 77
                )
              ),

              '48' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 76
                )
              ),

              '49' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 76
                )
              ),

              '50' => Array
              (
                '0' => Array
                (
                  '0' => 41,
                  '1' => 76
                )
              ),

              '51' => Array
              (
                '0' => Array
                (
                  '0' => 43,
                  '1' => 59
                ),
                '1' => Array
                (
                  '0' => 62,
                  '1' => 75
                )
              ),

              '52' => Array
              (
                '0' => Array
                (
                  '0' => 46,
                  '1' => 55
                ),
                '1' => Array
                (
                  '0' => 61,
                  '1' => 75
                )
              ),

              '53' => Array
              (
                '0' => Array
                (
                  '0' => 61,
                  '1' => 74
                )
              ),

              '54' => Array
              (
                '0' => Array
                (
                  '0' => 60,
                  '1' => 74
                )
              ),

              '55' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 23
                ),
                '1' => Array
                (
                  '0' => 60,
                  '1' => 73
                )
              ),

              '56' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 25
                ),
                '1' => Array
                (
                  '0' => 60,
                  '1' => 73
                )
              ),

              '57' => Array
              (
                '0' => Array
                (
                  '0' => 17,
                  '1' => 26
                ),
                '1' => Array
                (
                  '0' => 59,
                  '1' => 73
                )
              ),

              '58' => Array
              (
                '0' => Array
                (
                  '0' => 16,
                  '1' => 27
                ),
                '1' => Array
                (
                  '0' => 59,
                  '1' => 72
                )
              ),

              '59' => Array
              (
                '0' => Array
                (
                  '0' => 15,
                  '1' => 28
                ),
                '1' => Array
                (
                  '0' => 59,
                  '1' => 72
                )
              ),

              '60' => Array
              (
                '0' => Array
                (
                  '0' => 15,
                  '1' => 29
                ),
                '1' => Array
                (
                  '0' => 58,
                  '1' => 71
                )
              ),

              '61' => Array
              (
                '0' => Array
                (
                  '0' => 15,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 58,
                  '1' => 71
                )
              ),

              '62' => Array
              (
                '0' => Array
                (
                  '0' => 15,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 58,
                  '1' => 70
                )
              ),

              '63' => Array
              (
                '0' => Array
                (
                  '0' => 15,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 57,
                  '1' => 70
                )
              ),

              '64' => Array
              (
                '0' => Array
                (
                  '0' => 16,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 57,
                  '1' => 70
                )
              ),

              '65' => Array
              (
                '0' => Array
                (
                  '0' => 16,
                  '1' => 32
                ),
                '1' => Array
                (
                  '0' => 57,
                  '1' => 69
                )
              ),

              '66' => Array
              (
                '0' => Array
                (
                  '0' => 17,
                  '1' => 32
                ),
                '1' => Array
                (
                  '0' => 56,
                  '1' => 69
                )
              ),

              '67' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 32
                ),
                '1' => Array
                (
                  '0' => 56,
                  '1' => 69
                )
              ),

              '68' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 33
                ),
                '1' => Array
                (
                  '0' => 56,
                  '1' => 68
                )
              ),

              '69' => Array
              (
                '0' => Array
                (
                  '0' => 20,
                  '1' => 33
                ),
                '1' => Array
                (
                  '0' => 56,
                  '1' => 68
                )
              ),

              '70' => Array
              (
                '0' => Array
                (
                  '0' => 20,
                  '1' => 34
                ),
                '1' => Array
                (
                  '0' => 56,
                  '1' => 68
                )
              ),

              '71' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 34
                ),
                '1' => Array
                (
                  '0' => 56,
                  '1' => 68
                )
              ),

              '72' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 34
                ),
                '1' => Array
                (
                  '0' => 56,
                  '1' => 68
                )
              ),

              '73' => Array
              (
                '0' => Array
                (
                  '0' => 22,
                  '1' => 35
                ),
                '1' => Array
                (
                  '0' => 55,
                  '1' => 68
                )
              ),

              '74' => Array
              (
                '0' => Array
                (
                  '0' => 22,
                  '1' => 35
                ),
                '1' => Array
                (
                  '0' => 55,
                  '1' => 67
                )
              ),

              '75' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 36
                ),
                '1' => Array
                (
                  '0' => 55,
                  '1' => 67
                )
              ),

              '76' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 36
                ),
                '1' => Array
                (
                  '0' => 54,
                  '1' => 67
                )
              ),

              '77' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 37
                ),
                '1' => Array
                (
                  '0' => 54,
                  '1' => 67
                )
              ),

              '78' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 38
                ),
                '1' => Array
                (
                  '0' => 53,
                  '1' => 66
                )
              ),

              '79' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 39
                ),
                '1' => Array
                (
                  '0' => 53,
                  '1' => 66
                )
              ),

              '80' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 40
                ),
                '1' => Array
                (
                  '0' => 52,
                  '1' => 66
                )
              ),

              '81' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 41
                ),
                '1' => Array
                (
                  '0' => 52,
                  '1' => 66
                )
              ),

              '82' => Array
              (
                '0' => Array
                (
                  '0' => 26,
                  '1' => 43
                ),
                '1' => Array
                (
                  '0' => 51,
                  '1' => 65
                )
              ),

              '83' => Array
              (
                '0' => Array
                (
                  '0' => 26,
                  '1' => 65
                )
              ),

              '84' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 64
                )
              ),

              '85' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 64
                )
              ),

              '86' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 63
                )
              ),

              '87' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 63
                )
              ),

              '88' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 62
                )
              ),

              '89' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 62
                )
              ),

              '90' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 61
                )
              ),

              '91' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 61
                )
              ),

              '92' => Array
              (
                '0' => Array
                (
                  '0' => 35,
                  '1' => 60
                )
              ),

              '93' => Array
              (
                '0' => Array
                (
                  '0' => 37,
                  '1' => 58
                )
              ),

              '94' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 56
                )
              ),

              '95' => Array
              (
                '0' => Array
                (
                  '0' => 41,
                  '1' => 54
                )
              )
            );

            $char_0 = array(
              '5' => Array
              (
                '0' => Array
                (
                  '0' => 38,
                  '1' => 55
                )
              ),

              '6' => Array
              (
                '0' => Array
                (
                  '0' => 35,
                  '1' => 58
                )
              ),

              '7' => Array
              (
                '0' => Array
                (
                  '0' => 32,
                  '1' => 61
                )
              ),

              '8' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 63
                )
              ),

              '9' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 64
                )
              ),

              '10' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 65
                )
              ),

              '11' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 66
                )
              ),

              '12' => Array
              (
                '0' => Array
                (
                  '0' => 26,
                  '1' => 68
                )
              ),

              '13' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 69
                )
              ),

              '14' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 69
                )
              ),

              '15' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 70
                )
              ),

              '16' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 70
                )
              ),

              '17' => Array
              (
                '0' => Array
                (
                  '0' => 22,
                  '1' => 70
                )
              ),

              '18' => Array
              (
                '0' => Array
                (
                  '0' => 22,
                  '1' => 39
                ),
                '1' => Array
                (
                  '0' => 54,
                  '1' => 71
                )
              ),

              '19' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 36
                ),
                '1' => Array
                (
                  '0' => 56,
                  '1' => 71
                )
              ),

              '20' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 35
                ),
                '1' => Array
                (
                  '0' => 57,
                  '1' => 72
                )
              ),

              '21' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 35
                ),
                '1' => Array
                (
                  '0' => 57,
                  '1' => 72
                )
              ),

              '22' => Array
              (
                '0' => Array
                (
                  '0' => 20,
                  '1' => 34
                ),
                '1' => Array
                (
                  '0' => 58,
                  '1' => 73
                )
              ),

              '23' => Array
              (
                '0' => Array
                (
                  '0' => 20,
                  '1' => 34
                ),
                '1' => Array
                (
                  '0' => 57,
                  '1' => 73
                )
              ),

              '24' => Array
              (
                '0' => Array
                (
                  '0' => 20,
                  '1' => 33
                ),
                '1' => Array
                (
                  '0' => 57,
                  '1' => 73
                )
              ),

              '25' => Array
              (
                '0' => Array
                (
                  '0' => 20,
                  '1' => 32
                ),
                '1' => Array
                (
                  '0' => 57,
                  '1' => 73
                )
              ),

              '26' => Array
              (
                '0' => Array
                (
                  '0' => 20,
                  '1' => 32
                ),
                '1' => Array
                (
                  '0' => 56,
                  '1' => 74
                )
              ),

              '27' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 32
                ),
                '1' => Array
                (
                  '0' => 56,
                  '1' => 74
                )
              ),

              '28' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 32
                ),
                '1' => Array
                (
                  '0' => 55,
                  '1' => 74
                )
              ),

              '29' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 32
                ),
                '1' => Array
                (
                  '0' => 55,
                  '1' => 74
                )
              ),

              '30' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 55,
                  '1' => 74
                )
              ),

              '31' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 54,
                  '1' => 74
                )
              ),

              '32' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 54,
                  '1' => 74
                )
              ),

              '33' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 54,
                  '1' => 74
                )
              ),

              '34' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 53,
                  '1' => 75
                )
              ),

              '35' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 53,
                  '1' => 75
                )
              ),

              '36' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 52,
                  '1' => 75
                )
              ),

              '37' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 51,
                  '1' => 75
                )
              ),

              '38' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 51,
                  '1' => 75
                )
              ),

              '39' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 50,
                  '1' => 75
                )
              ),

              '40' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 50,
                  '1' => 76
                )
              ),

              '41' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 50,
                  '1' => 76
                )
              ),

              '42' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 49,
                  '1' => 76
                )
              ),

              '43' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 49,
                  '1' => 62
                ),
                '2' => Array
                (
                  '0' => 64,
                  '1' => 76
                )
              ),

              '44' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 49,
                  '1' => 61
                ),
                '2' => Array
                (
                  '0' => 64,
                  '1' => 76
                )
              ),

              '45' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 48,
                  '1' => 61
                ),
                '2' => Array
                (
                  '0' => 64,
                  '1' => 77
                )
              ),

              '46' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 48,
                  '1' => 61
                ),
                '2' => Array
                (
                  '0' => 65,
                  '1' => 77
                )
              ),

              '47' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 48,
                  '1' => 60
                ),
                '2' => Array
                (
                  '0' => 65,
                  '1' => 77
                )
              ),

              '48' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 47,
                  '1' => 60
                ),
                '2' => Array
                (
                  '0' => 65,
                  '1' => 77
                )
              ),

              '49' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 47,
                  '1' => 59
                ),
                '2' => Array
                (
                  '0' => 65,
                  '1' => 77
                )
              ),

              '50' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 46,
                  '1' => 59
                ),
                '2' => Array
                (
                  '0' => 65,
                  '1' => 78
                )
              ),

              '51' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 46,
                  '1' => 59
                ),
                '2' => Array
                (
                  '0' => 66,
                  '1' => 78
                )
              ),

              '52' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 45,
                  '1' => 58
                ),
                '2' => Array
                (
                  '0' => 66,
                  '1' => 78
                )
              ),

              '53' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 45,
                  '1' => 58
                ),
                '2' => Array
                (
                  '0' => 66,
                  '1' => 78
                )
              ),

              '54' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 44,
                  '1' => 58
                ),
                '2' => Array
                (
                  '0' => 66,
                  '1' => 78
                )
              ),

              '55' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 44,
                  '1' => 57
                ),
                '2' => Array
                (
                  '0' => 66,
                  '1' => 78
                )
              ),

              '56' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 43,
                  '1' => 57
                ),
                '2' => Array
                (
                  '0' => 66,
                  '1' => 78
                )
              ),

              '57' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 43,
                  '1' => 57
                ),
                '2' => Array
                (
                  '0' => 66,
                  '1' => 78
                )
              ),

              '58' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 43,
                  '1' => 56
                ),
                '2' => Array
                (
                  '0' => 66,
                  '1' => 78
                )
              ),

              '59' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 42,
                  '1' => 56
                ),
                '2' => Array
                (
                  '0' => 66,
                  '1' => 78
                )
              ),

              '60' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 42,
                  '1' => 55
                ),
                '2' => Array
                (
                  '0' => 66,
                  '1' => 78
                )
              ),

              '61' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 42,
                  '1' => 54
                ),
                '2' => Array
                (
                  '0' => 66,
                  '1' => 78
                )
              ),

              '62' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 41,
                  '1' => 54
                ),
                '2' => Array
                (
                  '0' => 66,
                  '1' => 78
                )
              ),

              '63' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 41,
                  '1' => 54
                ),
                '2' => Array
                (
                  '0' => 66,
                  '1' => 78
                )
              ),

              '64' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 41,
                  '1' => 53
                ),
                '2' => Array
                (
                  '0' => 66,
                  '1' => 78
                )
              ),

              '65' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 40,
                  '1' => 53
                ),
                '2' => Array
                (
                  '0' => 65,
                  '1' => 78
                )
              ),

              '66' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 40,
                  '1' => 53
                ),
                '2' => Array
                (
                  '0' => 65,
                  '1' => 78
                )
              ),

              '67' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 30
                ),
                '1' => Array
                (
                  '0' => 39,
                  '1' => 52
                ),
                '2' => Array
                (
                  '0' => 65,
                  '1' => 77
                )
              ),

              '68' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 39,
                  '1' => 52
                ),
                '2' => Array
                (
                  '0' => 64,
                  '1' => 77
                )
              ),

              '69' => Array
              (
                '0' => Array
                (
                  '0' => 18,
                  '1' => 31
                ),
                '1' => Array
                (
                  '0' => 38,
                  '1' => 52
                ),
                '2' => Array
                (
                  '0' => 64,
                  '1' => 77
                )
              ),

              '70' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 32
                ),
                '1' => Array
                (
                  '0' => 38,
                  '1' => 51
                ),
                '2' => Array
                (
                  '0' => 63,
                  '1' => 76
                )
              ),

              '71' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 32
                ),
                '1' => Array
                (
                  '0' => 37,
                  '1' => 51
                ),
                '2' => Array
                (
                  '0' => 62,
                  '1' => 76
                )
              ),

              '72' => Array
              (
                '0' => Array
                (
                  '0' => 19,
                  '1' => 33
                ),
                '1' => Array
                (
                  '0' => 37,
                  '1' => 50
                ),
                '2' => Array
                (
                  '0' => 61,
                  '1' => 76
                )
              ),

              '73' => Array
              (
                '0' => Array
                (
                  '0' => 20,
                  '1' => 34
                ),
                '1' => Array
                (
                  '0' => 37,
                  '1' => 50
                ),
                '2' => Array
                (
                  '0' => 60,
                  '1' => 75
                )
              ),

              '74' => Array
              (
                '0' => Array
                (
                  '0' => 20,
                  '1' => 49
                ),
                '1' => Array
                (
                  '0' => 59,
                  '1' => 75
                )
              ),

              '75' => Array
              (
                '0' => Array
                (
                  '0' => 20,
                  '1' => 49
                ),
                '1' => Array
                (
                  '0' => 57,
                  '1' => 75
                )
              ),

              '76' => Array
              (
                '0' => Array
                (
                  '0' => 21,
                  '1' => 48
                ),
                '1' => Array
                (
                  '0' => 55,
                  '1' => 74
                )
              ),

              '77' => Array
              (
                '0' => Array
                (
                  '0' => 22,
                  '1' => 48
                ),
                '1' => Array
                (
                  '0' => 54,
                  '1' => 73
                )
              ),

              '78' => Array
              (
                '0' => Array
                (
                  '0' => 22,
                  '1' => 48
                ),
                '1' => Array
                (
                  '0' => 52,
                  '1' => 73
                )
              ),

              '79' => Array
              (
                '0' => Array
                (
                  '0' => 23,
                  '1' => 72
                )
              ),

              '80' => Array
              (
                '0' => Array
                (
                  '0' => 24,
                  '1' => 71
                )
              ),

              '81' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 70
                )
              ),

              '82' => Array
              (
                '0' => Array
                (
                  '0' => 25,
                  '1' => 69
                )
              ),

              '83' => Array
              (
                '0' => Array
                (
                  '0' => 26,
                  '1' => 68
                )
              ),

              '84' => Array
              (
                '0' => Array
                (
                  '0' => 26,
                  '1' => 66
                )
              ),

              '85' => Array
              (
                '0' => Array
                (
                  '0' => 27,
                  '1' => 65
                )
              ),

              '86' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 63
                )
              ),

              '87' => Array
              (
                '0' => Array
                (
                  '0' => 28,
                  '1' => 61
                )
              ),

              '88' => Array
              (
                '0' => Array
                (
                  '0' => 29,
                  '1' => 60
                )
              ),

              '89' => Array
              (
                '0' => Array
                (
                  '0' => 30,
                  '1' => 58
                )
              ),

              '90' => Array
              (
                '0' => Array
                (
                  '0' => 31,
                  '1' => 56
                )
              ),

              '91' => Array
              (
                '0' => Array
                (
                  '0' => 33,
                  '1' => 53
                )
              ),

              '92' => Array
              (
                '0' => Array
                (
                  '0' => 34,
                  '1' => 50
                )
              ),

              '93' => Array
              (
                '0' => Array
                (
                  '0' => 36,
                  '1' => 48
                )
              )
            );

            return array(
                0 => $char_0,
                1 => $char_1,
                2 => $char_2,
                3 => $char_3,
                4 => $char_4,
                5 => $char_5,
                6 => $char_6,
                7 => $char_7,
                8 => $char_8,
                9 => $char_9
            );
        }
    }

?>