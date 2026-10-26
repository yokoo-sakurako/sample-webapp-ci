<?php
// tests/TaskServiceTest.php

use PHPUnit\Framework\TestCase;

// テスト対象のファイルを読み込む
require_once __DIR__ . '/../src/TaskService.php';

class TaskServiceTest extends TestCase {
    
    /**
     * テスト1: タイトルの入力チェック（バリデーション）が正しく動くか？
     */
    public function testValidateTitle() {
        $service = new TaskService();

        // ⭕️ 正常系：正しい文字数の場合（Trueになることを期待）
        $this->assertTrue($service->validateTitle('買い物に行く'));

        // ❌ 異常系：空文字やスペースのみの場合（Falseになることを期待）
        $this->assertFalse($service->validateTitle(''));
        $this->assertFalse($service->validateTitle('   '));

        // ❌ 異常系：51文字以上の場合（Falseになることを期待）
        $longTitle = str_repeat('あ', 51); // 「あ」を51回繰り返す
        $this->assertFalse($service->validateTitle($longTitle));
    }

    /**
     * テスト2: タスクの絞り込みと並び替えが正しく動くか？
     */
    public function testFilterAndSortTasks() {
        $service = new TaskService();

        // テスト用の「ダミー配列データ」（完了済みが混ざっていて、順不同）
        $dummyTasks = [
            ['id' => 1, 'title' => 'タスクA', 'is_completed' => false],
            ['id' => 2, 'title' => 'タスクB（完了済）', 'is_completed' => true],
            ['id' => 3, 'title' => 'タスクC', 'is_completed' => false],
        ];

        // 実際にロジックを通してみる
        $result = $service->filterAndSortTasks($dummyTasks);

        // 【検証1】完了済みのタスクBが除外され、全体が「2件」になっているか？
        $this->assertCount(2, $result);

        // 【検証2】IDが大きい順（新しい順）に並び替わっているか？
        // 期待される順番は ID:3 -> ID:1
        $this->assertEquals(3, $result[0]['id']); // 1番目は ID:3 であるべき
        $this->assertEquals(1, $result[1]['id']); // 2番目は ID:1 であるべき
    }
}
