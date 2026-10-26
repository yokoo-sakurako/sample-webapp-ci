<?php
// src/TaskService.php

class TaskService {
    /**
     * 1. 入力されたタスク名のチェック（テスト対象ロジック①：バリデーション）
     */
    public function validateTitle(string $title): bool {
        $trimmed = trim($title);
        // 空っぽはNG
        if ($trimmed === '') {
            return false; 
        }
        // 50文字を超えたらNG
        if (mb_strlen($trimmed) > 50) {
            return false; 
        }
        return true;
    }

    /**
     * 2. タスク一覧の整理（テスト対象ロジック②：配列操作）
     *   - 未完了のタスクだけを残す
     *   - IDの降順（新しく追加した順）に並び替える
     */
    public function filterAndSortTasks(array $tasks): array {
        // 未完了のタスクのみ抽出
        $activeTasks = array_filter($tasks, function($task) {
            return !$task['is_completed'];
        });

        // 新しいタスク（IDが大きいもの）が上に来るように並び替え
        usort($activeTasks, function($a, $b) {
            return $b['id'] <=> $a['id'];
        });

        return array_values($activeTasks);
    }
}
