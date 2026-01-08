<?php
use App\Core\Auth;
$title = 'Campaigns - Edit - ' . APP_NAME;
$errors = $errors ?? [];
$campaign = $campaign ?? [];
?>

<div class="campaign-edit" style="padding: 2rem; max-width: 1000px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 style="margin: 0; color: var(--text-primary);">Edit Campaign: <?= $viewHelper->escape($campaign['name'] ?? '') ?></h1>
        <a class="btn btn-secondary" href="<?= $viewHelper->url('campaigns/index') ?>&instance=<?= $viewHelper->escape($_GET['instance'] ?? '') ?>">Back</a>
    </div>

    <!-- Campaign Details -->
    <div style="background: var(--surface); border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 2rem;">
        <h3 style="margin-bottom: 1rem; color: var(--text-primary);">📋 Campaign Details</h3>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div>
                <strong>Name:</strong> <?= $viewHelper->escape($campaign['name'] ?? '') ?>
            </div>
            <div>
                <strong>Status:</strong> 
                <span style="color: <?= $campaign['is_active'] ? 'var(--success)' : 'var(--error)' ?>;">
                    <?= $campaign['is_active'] ? '🟢 Active' : '🔴 Inactive' ?>
                </span>
            </div>
            <div>
                <strong>Schedule:</strong> 
                <?php
                switch ($campaign['schedule_type']) {
                    case 'once':
                        echo 'Once - ' . $viewHelper->formatDate($campaign['start_at'] ?? '', 'M j, Y H:i');
                        break;
                    case 'daily':
                        echo 'Daily at ' . $viewHelper->formatDate($campaign['start_at'] ?? '', 'H:i');
                        break;
                    case 'weekly':
                        if ($campaign['weekly_days']) {
                            $days = explode(',', $campaign['weekly_days']);
                            $dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                            $dayLabels = [];
                            foreach ($days as $day) {
                                if (isset($dayNames[$day - 1])) {
                                    $dayLabels[] = $dayNames[$day - 1];
                                }
                            }
                            echo 'Weekly (' . implode(', ', $dayLabels) . ') at ' . $viewHelper->formatDate($campaign['start_at'] ?? '', 'H:i');
                        } else {
                            echo 'Weekly';
                        }
                        break;
                    case 'monthly':
                        echo 'Monthly (Day ' . ($campaign['monthly_day'] ?? '') . ') at ' . $viewHelper->formatDate($campaign['start_at'] ?? '', 'H:i');
                        break;
                    default:
                        echo $viewHelper->escape($campaign['schedule_type'] ?? '');
                }
                ?>
            </div>
            <div>
                <strong>Next Run:</strong> 
                <?= $campaign['next_run_at'] ? $viewHelper->formatDate($campaign['next_run_at'], 'M j, Y H:i') : '—' ?>
            </div>
        </div>
        
        <?php if ($campaign['end_at']): ?>
        <div style="margin-top: 1rem;">
            <strong>End Date:</strong> <?= $viewHelper->formatDate($campaign['end_at'], 'M j, Y H:i') ?>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 1rem;">
            <strong>Created:</strong> <?= $viewHelper->formatDate($campaign['created_at'], 'M j, Y H:i') ?> by <?= $viewHelper->escape($campaign['created_by_name'] ?? '') ?>
        </div>
    </div>

    <!-- Campaign Messages Section -->
    <div class="campaign-messages" style="margin-top: 2rem; background: var(--surface); border-radius: 0.5rem; padding: 1.5rem;">
        <h3 style="margin-bottom: 1rem; color: var(--text-primary);">📝 Campaign Messages</h3>
        
        <div id="messages-container">
            <?php if (!empty($messages)): ?>
                <?php foreach ($messages as $index => $message): ?>
                    <div class="message-item" style="display: flex; gap: 1rem; align-items: start; margin-bottom: 1rem; padding: 1rem; background: var(--background); border-radius: 0.5rem; border: 1px solid var(--border);">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                <span style="background: var(--primary); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;"><?= $index + 1 ?></span>
                                <strong style="margin-left: 0.5rem;">Message #<?= $index + 1 ?></strong>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Message Text</label>
                                <textarea class="form-input" name="messages[<?= $index ?>][text]" rows="3" placeholder="Enter message text..."><?= $viewHelper->escape($message['text'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Caption (optional)</label>
                                <input class="form-input" type="text" name="messages[<?= $index ?>][caption]" value="<?= $viewHelper->escape($message['caption'] ?? '') ?>" placeholder="Media caption">
                            </div>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <button type="button" class="btn btn-sm btn-secondary" onclick="removeMessage(<?= $index ?>)" style="align-self: start;">🗑️ Remove</button>
                            <button type="button" class="btn btn-sm btn-primary" onclick="moveMessageUp(<?= $index ?>)" style="align-self: start;">↑</button>
                            <button type="button" class="btn btn-sm btn-primary" onclick="moveMessageDown(<?= $index ?>)" style="align-self: start;">↓</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <button type="button" class="btn btn-secondary" onclick="addMessage()" style="margin-top: 1rem;">➕ Add Message</button>
    </div>

    <!-- Campaign Targets Section -->
    <div class="campaign-targets" style="margin-top: 2rem; background: var(--surface); border-radius: 0.5rem; padding: 1.5rem;">
        <h3 style="margin-bottom: 1rem; color: var(--text-primary);">🎯 Campaign Targets</h3>
        
        <div id="targets-container">
            <?php if (!empty($targets)): ?>
                <?php foreach ($targets as $index => $target): ?>
                    <div class="target-item" style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem; padding: 1rem; background: var(--background); border-radius: 0.5rem; border: 1px solid var(--border);">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                <span style="background: var(--primary); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;"><?= $index + 1 ?></span>
                                <strong style="margin-left: 0.5rem;">Target #<?= $index + 1 ?></strong>
                                <span style="margin-left: 1rem; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; background: <?= $target['target_type'] === 'contact' ? 'var(--success)' : 'var(--info)' ?>; color: white;">
                                    <?= ucfirst($target['target_type'] ?? '') ?>
                                </span>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Target Type</label>
                                <select class="form-input" name="targets[<?= $index ?>][type]">
                                    <option value="contact" <?= ($target['target_type'] ?? '') === 'contact' ? 'selected' : '' ?>>👤 Contact</option>
                                    <option value="list" <?= ($target['target_type'] ?? '') === 'list' ? 'selected' : '' ?>>👥 Contact List</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Target ID / Name</label>
                                <input class="form-input" type="text" name="targets[<?= $index ?>][id]" value="<?= $viewHelper->escape($target['target_id'] ?? '') ?>" placeholder="Contact ID or List ID">
                                <?php if (!empty($target['target_name'])): ?>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                        📄 <?= $viewHelper->escape($target['target_name']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-sm btn-secondary" onclick="removeTarget(<?= $index ?>)" style="align-self: start;">🗑️ Remove</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
            <button type="button" class="btn btn-secondary" onclick="addContactTarget()" style="margin-top: 1rem;">👤 Add Contact</button>
            <button type="button" class="btn btn-secondary" onclick="addListTarget()" style="margin-top: 1rem;">👥 Add Contact List</button>
        </div>
    </div>

    <form method="post" action="<?= $viewHelper->url('campaigns/update') ?>&instance=<?= $viewHelper->escape($_GET['instance'] ?? '') ?>&id=<?= $viewHelper->escape($campaign['id']) ?>" style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.5rem;">
        <div class="form-group">
            <label class="form-label" for="name">Campaign Name</label>
            <input class="form-input" id="name" name="name" type="text" required value="<?= $viewHelper->escape($campaign['name'] ?? '') ?>">
            <?php if (isset($errors['name'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['name']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="schedule_type">Schedule Type</label>
            <select class="form-input" id="schedule_type" name="schedule_type" required onchange="showScheduleFields(this.value)">
                <option value="once" <?= ($data['schedule_type'] ?? $campaign['schedule_type'] ?? '') === 'once' ? 'selected' : '' ?>>Run Once</option>
                <option value="daily" <?= ($data['schedule_type'] ?? $campaign['schedule_type'] ?? '') === 'daily' ? 'selected' : '' ?>>Daily</option>
                <option value="weekly" <?= ($data['schedule_type'] ?? $campaign['schedule_type'] ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                <option value="monthly" <?= ($data['schedule_type'] ?? $campaign['schedule_type'] ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
            </select>
        </div>

        <div class="form-group" id="date-fields" style="display: block;">
            <label class="form-label" for="start_date">Start Date</label>
            <input class="form-input" id="start_date" name="start_date" type="date" required value="<?= $viewHelper->escape($data['start_date'] ?? $campaign['start_date'] ?? '') ?>" 
                   min="<?= date('Y-m-d') ?>" 
                   max="<?= date('Y-m-d', strtotime('+1 year')) ?>">
            <?php if (isset($errors['start_date'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['start_date']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group" id="time-fields" style="display: block;">
            <label class="form-label" for="start_time">Start Time</label>
            <input class="form-input" id="start_time" name="start_time" type="time" required value="<?= $viewHelper->escape($data['start_time'] ?? $campaign['start_time'] ?? '') ?>">
            <?php if (isset($errors['start_time'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['start_time']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group" id="date-fields" style="display: block;">
            <label class="form-label" for="end_date">End Date (optional)</label>
            <input class="form-input" id="end_date" name="end_date" type="date" value="<?= $viewHelper->escape($data['end_date'] ?? $campaign['end_date'] ?? '') ?>" 
                   min="<?= date('Y-m-d') ?>" 
                   max="<?= date('Y-m-d', strtotime('+1 year')) ?>">
            <?php if (isset($errors['end_date'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['end_date']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group" id="time-fields" style="display: block;">
            <label class="form-label" for="end_time">End Time (optional)</label>
            <input class="form-input" id="end_time" name="end_time" type="time" value="<?= $viewHelper->escape($data['end_time'] ?? $campaign['end_time'] ?? '') ?>">
            <?php if (isset($errors['end_time'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['end_time']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group" id="weekly-fields" style="display: <?= ($data['schedule_type'] ?? $campaign['schedule_type'] ?? '') === 'weekly' ? 'block' : 'none' ?>;">
            <label class="form-label">Días de la semana</label>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <?php foreach (['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'] as $day): ?>
                    <label style="display: flex; align-items: center; gap: 0.25rem; cursor: pointer;">
                        <input type="checkbox" name="weekly_days[]" value="<?= $day ?>" 
                               <?= (isset($data['weekly_days']) && in_array($day, explode(',', $data['weekly_days']))) ? 'checked' : '' ?>
                               <?= (isset($campaign['weekly_days']) && in_array($day, explode(',', $campaign['weekly_days']))) ? 'checked' : '' ?> >
                        <span><?= $day ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group" id="monthly-fields" style="display: <?= ($data['schedule_type'] ?? $campaign['schedule_type'] ?? '') === 'monthly' ? 'block' : 'none' ?>;">
            <label class="form-label">Día del mes</label>
            <input class="form-input" id="monthly_day" name="monthly_day" type="number" min="1" max="31" value="<?= $data['monthly_day'] ?? $campaign['monthly_day'] ?? '' ?>">
        </div>

        <div class="form-group" id="daily-fields" style="display: <?= ($data['schedule_type'] ?? $campaign['schedule_type'] ?? '') === 'daily' ? 'block' : 'none' ?>;">
            <label class="form-label">Hora de ejecución</label>
            <input class="form-input" id="daily_time" name="daily_time" type="time" value="<?= $data['daily_time'] ?? $campaign['daily_time'] ?? '09:00' ?>">
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="is_active" value="1" <?= (int)($campaign['is_active'] ?? 0) === 1 ? 'checked' : '' ?>>
                <span style="color: var(--text-primary);">Active</span>
            </label>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a class="btn btn-secondary" href="<?= $viewHelper->url('campaigns/index') ?>&instance=<?= $viewHelper->escape($_GET['instance'] ?? '') ?>">Cancel</a>
        </div>
    </form>
</div>
