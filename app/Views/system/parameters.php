<?php
use App\Core\Auth;
$title = 'System Parameters - ' . APP_NAME;
?>

<div class="system-parameters" style="padding: 1rem;">
    <!-- Header -->
    <div class="parameters-header" style="margin-bottom: 1.5rem; padding: 0 1rem;">
        <h2 style="color: var(--text-primary); margin-bottom: 0.25rem;">System Parameters</h2>
        <p style="color: var(--text-secondary); font-size: 0.875rem;">
            Configure global system settings
        </p>
    </div>

    <!-- Parameters Form -->
    <div class="parameters-form" style="background: var(--surface); border-radius: 0.75rem; padding: 1.5rem; margin: 0 1rem; border: 1px solid var(--border);">
        <form method="POST" id="parameters-form">
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label" for="max_campaign_days" style="display: block; font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">
                    Maximum Campaign Duration (days)
                </label>
                <input 
                    type="number" 
                    id="max_campaign_days" 
                    name="max_campaign_days" 
                    class="form-input" 
                    value="<?= $viewHelper->escape($maxCampaignDays) ?>"
                    min="1" 
                    max="36500"
                    style="width: 200px; padding: 0.6rem 0.75rem; border: 1px solid var(--border); border-radius: 0.25rem; background: var(--background); color: var(--text-primary);"
                    required
                >
                <div style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 0.5rem;">
                    When a campaign end date is specified without an end time, the system will automatically extend it by this many days.
                </div>
            </div>

            <div class="form-actions" style="display: flex; gap: 0.75rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="padding: 0.55rem 1.2rem; border: none; border-radius: 0.25rem; cursor: pointer; font-size: 0.9rem;">
                    Save Parameters
                </button>
                <a href="index.php?r=dashboard/index" class="btn btn-secondary" style="padding: 0.55rem 1.2rem; border: none; border-radius: 0.25rem; cursor: pointer; font-size: 0.9rem; text-decoration: none;">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <!-- Information Section -->
    <div class="info-section" style="background: var(--surface); border-radius: 0.75rem; padding: 1.5rem; margin: 1rem; border: 1px solid var(--border);">
        <h3 style="color: var(--text-primary); margin-bottom: 1rem;">How Campaign Duration Works</h3>
        
        <div style="color: var(--text-secondary); font-size: 0.875rem; line-height: 1.6;">
            <p style="margin-bottom: 1rem;">
                <strong>Scenario:</strong> User sets campaign start date as "2026-01-15" and end date as "2026-01-20" but leaves the end time empty.
            </p>
            
            <p style="margin-bottom: 1rem;">
                <strong>System Behavior:</strong> The campaign will automatically extend to "2026-01-20 23:59" plus the configured maximum days.
            </p>
            
            <p style="margin-bottom: 1rem;">
                <strong>Example with current setting (<?= $maxCampaignDays ?> days):</strong> End date becomes "<?= date('Y-m-d', strtotime('+' . $maxCampaignDays . ' days', strtotime('2026-01-20'))) ?> 23:59"
            </p>
            
            <div style="background: var(--background); padding: 1rem; border-radius: 0.5rem; border-left: 4px solid var(--primary); margin-top: 1rem;">
                <strong>💡 Tip:</strong> This prevents campaigns from ending unexpectedly when users forget to specify an end time.
            </div>
        </div>
    </div>
</div>

<style>
.form-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
}

.btn-secondary {
    background: var(--background);
    color: var(--text-primary);
    border: 1px solid var(--border);
}

.btn-secondary:hover {
    background: var(--border);
}
</style>

<script>
document.getElementById('parameters-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('index.php?r=system/parameters', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Parameters updated successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating parameters');
    });
});
</script>
