<div id="schedule-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden target:flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-2xl shadow-xl w-full max-w-md border border-slate-200 space-y-4 text-left">
        <div class="flex justify-between items-center">
            <h3 class="text-sm font-bold text-slate-800">Schedule Time Block</h3>
            <a href="#" class="text-slate-400 hover:text-slate-600 font-bold transition">✕</a>
        </div>

        <form action="backend/user_code.php" method="POST" class="space-y-3 text-xs">
            <input type="hidden" name="action" value="add_schedule_block">

            <div>
                <label class="block font-semibold text-slate-600 mb-1">Select Task</label>
                <select name="task_id" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg">
                    <option value="">-- Choose Task --</option>
                    <?php if (!empty($tasks)): ?>
                        <?php foreach ($tasks as $t): ?>
                            <option value="<?php echo $t['task_id']; ?>">
                                <?php echo htmlspecialchars($t['title']); ?> (<?php echo $t['estimated_minutes']; ?>m)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label class="block font-semibold text-slate-600 mb-1">Start Time</label>
                <input type="datetime-local" name="start_time" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg">
            </div>

            <div>
                <label class="block font-semibold text-slate-600 mb-1">End Time</label>
                <input type="datetime-local" name="end_time" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg">
            </div>

            <div class="flex gap-2 justify-end pt-3">
                <a href="#" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-600 font-semibold hover:bg-slate-50 transition">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold transition">Save Block</button>
            </div>
        </form>
    </div>
</div>  