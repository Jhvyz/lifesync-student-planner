<div id="edit-modal-<?php echo $row['task_id']; ?>" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden target:flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-2xl shadow-xl w-full max-w-md border border-slate-200 space-y-4 text-left">
        <div class="flex justify-between items-center">
            <h3 class="text-sm font-bold text-slate-800">Edit Task</h3>
            <a href="#" class="text-slate-400 hover:text-slate-600 font-bold transition">✕</a>
        </div>

        <form action="backend/user_code.php" method="POST" class="space-y-3 text-xs">
            <input type="hidden" name="action" value="update_task">
            <input type="hidden" name="task_id" value="<?php echo $row['task_id']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

            <div>
                <label class="block font-semibold text-slate-600 mb-1">Task Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($row['title']); ?>" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg">
            </div>

            <div>
                <label class="block font-semibold text-slate-600 mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"><?php echo htmlspecialchars($row['description'] ?? ''); ?></textarea>
            </div>

            <div>
                <label class="block font-semibold text-slate-600 mb-1">Category (Pillar)</label>
                <select name="pillar_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg">
                    <option value="1" <?php echo ($row['pillar_id'] == 1) ? 'selected' : ''; ?>>Academics</option>
                    <option value="2" <?php echo ($row['pillar_id'] == 2) ? 'selected' : ''; ?>>Household Chores</option>
                    <option value="3" <?php echo ($row['pillar_id'] == 3) ? 'selected' : ''; ?>>Recovery / Self-Care</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Est. Minutes</label>
                    <input type="number" name="estimated_minutes" value="<?php echo $row['estimated_minutes']; ?>" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg">
                </div>
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Mental Effort</label>
                    <select name="effort_level" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg">
                        <option value="Low" <?php echo ($row['effort_level'] == 'Low') ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo ($row['effort_level'] == 'Medium') ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?php echo ($row['effort_level'] == 'High') ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-semibold text-slate-600 mb-1">Due Date & Time</label>
                <input type="datetime-local" name="due_date" value="<?php echo !empty($row['due_date']) ? date('Y-m-d\TH:i', strtotime($row['due_date'])) : ''; ?>" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg">
            </div>

            <div>
                <label class="block font-semibold text-slate-600 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg">
                    <option value="Pending" <?php echo ($row['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="In Progress" <?php echo ($row['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                    <option value="Completed" <?php echo ($row['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                    <option value="Postponed" <?php echo ($row['status'] == 'Postponed') ? 'selected' : ''; ?>>Postponed</option>
                </select>
            </div>

            <div class="flex gap-2 justify-end pt-3">
                <a href="#" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-600 font-semibold hover:bg-slate-50 transition">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold transition">Update Task</button>
            </div>
        </form>
    </div>
</div>