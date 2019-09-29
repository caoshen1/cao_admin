<?php
/**
 * Created by PhpStorm.
 * User: 曹珅
 * Date: 2019/6/13
 * Time: 20:09
 */

namespace mytools\office;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class MyExcel
{
    // 支持文件类型
    private $file_prefix = [
        'xls' => 'PhpOffice\\PhpSpreadsheet\\Writer\\Xls',
        'xlsx' => 'PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx',
        'html',
        'csv',
        'pdf'
    ];

    // 储存路径
    private $file_path = false;

    // 当前对象
    private $ExcelInstence;

    // 文件保存类型
    protected $prefix = '';

    // 列标号
    protected $field_no = [];

    public function __construct()
    {
        if(!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) dieReturn('请先安装php-offic支持包：composer require phpoffice/phpspreadsheet');
        $this->field_no = array_combine(range(1,26),range('A','Z'));
    }

    /**
     * 创建Excel
     * @param array $data 表格数据
     * @param string $type 文件类型
     * @param string $path 储存路径
     * @return array
     */
    public function create(array $data, string $type = 'xls', string $path = '')
    {
        /*$data = [
            //'save_name' => '测试表格1',
            'table' => [
                // 表格1
                'sheet1' => [
                    // 工作表标题
                    'title' => '测试表格',
                    // 表格标题
                    'table_captain' => '表格标题',
                    // 边框
                    'border'=> true,
                    // 字段
                    'field' => [
                        'ID',
                        '姓名',
                        [
                            '手机',
                            [
                                'width' => 20,
                                'font' => ['黑体',11,true,'FF00FF00'],
                                'text-align' => 'center',
                                'border' => true,
                            ],
                        ],
                    ],
                    // 数据
                    'content' => [
                        [1,'张三','13667098267'],
                        [2,'李四','15179165971'],
                        [3,'王五','13479899685'],
                    ],
                ],
            ],

        ];*/
        $this->ExcelInstence = new Spreadsheet();
        if($path) $this->file_path = $path; // 设置文件保存路径
        $this->setSaveType($type); // 设置文件保存类型

        $sheet = $this->ExcelInstence->getActiveSheet();
        // 处理数据
        $this->handleData($data,$sheet);
        // 输出
        $this->saveExcel($data['save_name'] ?? false);

        return serviceReturn(1);
    }

    // 处理数据
    public function handleData($data, &$sheet)
    {
        foreach ($data['table'] as $k => $v) {
            if(count($v['field']) > 200) dieReturn('表'.$k.'字段过多');
            // 设置标题
            if(!empty($v['title'])){
                $sheet->setTitle($v['title']);
            }
            // 开始行号
            $start_no = 1;
            // 设置表格大标题
            if(!empty($v['table_captain'])) {
                $sheet->mergeCells('A1:'. $this->fieldNo(count($v['field']) - 1).'1');
                $sheet->setCellValue('A1', $v['table_captain']);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setName('黑体');
                $sheet->getStyle('A1')->applyFromArray([
                        'alignment'=>[
                            'horizontal'=>'center',
                        ]
                    ]);
                $start_no = 2;
            }
            $count = count($v['content']);  //计算有多少条数据
            // 表格边框
            if(!empty($v['border'])){
                $border = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => $v['border']['style'] ?? 'thick' ,
                            'color' => ['argb' => $v['border']['color'] ?? 'FF000000'],
                        ],
                    ],
                ];
                $sheet->getStyle('A'.($start_no + 1).':'.$this->fieldNo(count($v['field']) - 1).($count+$start_no))->applyFromArray($border);
            }
            foreach ($v['field'] as $kk => $fie) { // 设置表头
                $fno = $this->fieldNo($kk).$start_no;
                $this->fieldOption($fno,$fie,$sheet);
            }
            for ($i = $start_no + 1; $i <= $count+$start_no; $i++) {
                foreach ($v['content'][$i-$start_no-1] as $kkk => $fv) {
                    $box = $this->fieldNo($kkk) . $i;
                    $sheet->setCellValue($box, $fv);
                    $fvstyle = [];
                    // 边框
                    if(!empty($v['border'])){
                        $fvstyle['borders'] = [
                            'outline'=>[
                                'borderStyle'=>'thin',
                                'color'=>['argb'=>'FF000000']
                            ]
                        ];
                    }
                    // 对其方式
                    if(!empty($v['field'][$kkk][1]['text-align'])){
                        $fvstyle['alignment']['horizontal'] = $v['field'][$kkk][1]['text-align'];
                    }
                    if($fvstyle) {
                        $sheet->getStyle($box)->applyFromArray($fvstyle);
                    }
                }
            }
        }
    }

    // 处理列属性
    public function fieldOption($fno,$field, &$sheet)
    {
        if(is_array($field)){ // [字段名,[属性列表(fw列宽)]]
            $sheet->setCellValue($fno, $field[0]);
            // 设置列宽
            if(isset($field[1]['width'])) {
                $sheet->getColumnDimension(rtrim($fno,'1,2'))->setWidth($field[1]['width'] ? $field[1]['width'] : true);
            }
            // 构建其他样式
            $style = [];
            // 对齐方式
            if(isset($field[1]['text-align']) && $field[1]['text-align']) {
                switch ($field[1]['text-align']){
                    case 'center':
                        $style['alignment']['horizontal'] = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER;
                        break;
                    case 'left':
                        $style['alignment']['horizontal'] = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT;
                        break;
                    case 'right':
                        $style['alignment']['horizontal'] = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT;
                        break;
                }
            }
            // 设置字体
            if(isset($field[1]['font']) && $field[1]['font']) {
                $sheet->getStyle($fno)->getFont()->setBold($field[1]['font'][2] ?? true)
                    ->setName($field[1]['font'][0] ?? 'Arial')
                    ->setSize($field[1]['font'][1] ?? 11)
                    ->getColor()
                    ->setARGB($field[1]['font'][3] ?? 'FF000000');
            }
            // 设置颜色
            if(isset($field[1]['color']) && $field[1]['color']) {
                $sheet->getStyle($fno)->getFont()->getColor()
                    ->setARGB($field[1]['color']);
            }
            // 设置边框
            if(isset($field[1]['border']) && $field[1]['border']) {
                $style['borders']['outline']['borderStyle'] = $field[1]['border']['style'] ?? 'thick';
                $style['borders']['outline']['color']['argb'] = $field[1]['border']['color'] ?? 'FFFF0000';
            }
            $sheet->getStyle($fno)->applyFromArray($style);
        }else{
            $sheet->setCellValue($fno, $field);
        }
    }

    // 生成列编码
    public function fieldNo($k)
    {
        if(is_int($k)) {
            $index = ceil($k / 26);
            $key = $k%26 ? $k%26 : 26;
            $str = '';
            if($index <= 1 ) return $this->field_no[$k + 1];
            $str .= $this->field_no[$index-1].$this->field_no[$key];
            return $str;
        }
        return $k;
    }

    /**
     * 设置表格选项
     * @param array $option 选项
     * @return $this
     */
    public function setOption(array $option)
    {
        return $this;
    }

    /**
     * 读取Excel
     * @param string $file_name 文件名
     * @param int $start 开始行号
     * @param int $num 取多少行 0表示取到结束
     * @return array 文件内容
     */
    public function read(string $file_name,$start = 2,$num = 0)
    {
        // 检查表后缀
        $suffer = explode('.',$file_name);
        $suffer = ucfirst($suffer[count($suffer) - 1]);
        // 获取读取类
        $reader = IOFactory::createReader($suffer);
        $reader->setReadDataOnly(TRUE);
        $spreadsheet = $reader->load($file_name); //载入excel表格

        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow(); // 总行数
        $highestColumn = $worksheet->getHighestColumn(); // 总列数
        $lines = $highestRow - 2;
        $data = []; // 表格数据
        if ($lines <= 0) {
            serviceReturn('Excel表格中没有数据');
        }
        $end = $num ? $num + $start : $highestRow;
        for ($row = $start; $row <= $end; $row++) {
            for ($column = 1; $column <= $highestColumn;$column++) {
                $data[$row][] = $worksheet->getCellByColumnAndRow($column, $row)->getValue();
            }
        }
        return $data;
    }

    /**
     * 通过模板创建表格
     * @param array $data 数据
     * @param string $temp 模板路径
     * @param int $start 数据开始行数
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function createByTemp(array $data, string $temp, $start = 2)
    {
        //通过工厂模式创建内容
        $spreadsheet = IOFactory::load($temp);
        // 检查表后缀
        $suffer = explode('.',$temp);
        $suffer = ucfirst($temp[count($suffer) - 1]);
        // 写表
        $worksheet = $spreadsheet->getActiveSheet();
        foreach ($data['data'] as $k => $v) {
            if(!empty($v)) {
                foreach ($v as $kk=>$vv) {
                    $worksheet->getCell($this->fieldNo($kk) . ($k + $start))->setValue($vv);
                }
            }
        }
        //通过工厂模式来写内容
        $writer = IOFactory::createWriter($spreadsheet, $suffer);
        $this->saveExcel($data['save_name'],$writer);
        return serviceReturn(1);
    }

    // 输出文件
    private function saveExcel($name, $writer = false)
    {
        if(!$writer) {
            $writer = new $this->file_prefix[$this->prefix]($this->ExcelInstence);
        }
        if($this->file_path){ // 保存到服务器
            $writer->save($this->file_path . $this->saveName($name));
            return serviceReturn(1);
        }else{ // 直接输出到浏览器
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$this->saveName($name).'"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            exit();
        }
    }

    // 设置保存类型
    public function setSaveType($type)
    {
        if(!key_exists($type,$this->file_prefix)) dieReturn('Excel导出格式错误');
        $this->prefix = $type;
    }

    // 保存文件名
    public function saveName($name = false)
    {
        if($name){
            return $name . '.' . $this->prefix;
        }
        return date('Ymd-His') . '.' . $this->prefix;
    }
}