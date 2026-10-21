<div id="delete-modal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
    <div class="bg-white border border-slate-200 rounded-2xl p-6 w-full max-w-md shadow-2xl space-y-4 text-center">
        <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto">
            <span aria-hidden="true">×</span>
        </div>
        
        <h3 class="text-lg font-bold text-slate-800">Delete Task</h3>
        <p class="text-sm text-slate-500">Sigurado ka nga gusto nimo idelete kini nga task? Dili na kini mabawi.</p>

        <div class="flex items-center justify-center gap-3 pt-3 border-t border-slate-100">
            <a href="index.php" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm transition">
                Cancel
            </a>
            <form action="backend/user_code.php" method="POST">
                <input type="hidden" name="action" value="delete_task">
                <input type="hidden" name="task_id" value="<?php echo (int)($row['task_id'] ?? 0); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-lg text-sm transition shadow-sm">
                    Confirm Delete
                </button>
            </form>
        </div>
    </div>
</div>