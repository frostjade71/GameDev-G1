<?php
require_once '../../../onboarding/config.php';
require_once '../../../includes/greeting.php';
require_once 'shard_manager.php';

// Check if user is logged in
requireLogin();

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$shardManager = new ShardManager($pdo);
$shard_balance = $shardManager->getShardBalance($user_id);
$transactions = $shardManager->getTransactionHistory($user_id, 100);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shard History - VocabWorld</title>
    <link rel="stylesheet" href="../style.css?v=3">
    <link rel="stylesheet" href="charactermenu.css?v=3">
    <link rel="stylesheet" href="../navigation/navigation.css?v=3">
    <link rel="stylesheet" href="../../../notif/toast.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="game-container">
        <!-- Background -->
        <div class="background-image"></div>
        
        <!-- Header -->
        <header class="top-header">
            <div class="header-left">
                <div class="game-logo-container">
                    <img src="../assets/vocabworldhead.png" alt="VocabWorld" class="game-header-logo">
                </div>
            </div>
            <div class="header-right">
                <div class="shard-currency">
                    <img src="../assets/currency/shard1.png" alt="Shards" class="shard-icon">
                    <span class="shard-count"><?php echo $shard_balance['current_shards'] ?? 0; ?></span>
                </div>
                <div class="user-profile">
                    <div class="user-info">
                        <span class="greeting"><?php echo getGreeting(); ?></span>
                        <span class="username"><?php echo htmlspecialchars(explode(' ', $user['username'])[0]); ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Shard History Screen -->
        <div id="shard-history-screen" class="screen active">
            <div class="character-profile-layout">
                <!-- Left Side: Shard Summary -->
                <div class="character-preview-section">
                    <div class="character-preview-card transparent-card">
                        <div class="character-preview-header">
                            <h3>Shard Account</h3>
                        </div>
                        <div class="shard-summary">
                            <div class="shard-balance">
                                <h4>Current Balance</h4>
                                <div class="balance-amount"><?php echo $shard_balance['current_shards'] ?? 0; ?> Shards</div>
                            </div>
                            <div class="shard-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Total Earned:</span>
                                    <span class="stat-value"><?php echo $shard_balance['total_earned'] ?? 0; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Total Spent:</span>
                                    <span class="stat-value"><?php echo $shard_balance['total_spent'] ?? 0; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="character-actions">
                            <button class="action-btn back-to-character-btn" onclick="goToCharacterProfile()">
                                <i class="fas fa-arrow-left"></i>
                                Back to Character
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side: Transaction History -->
                <div class="progress-section">
                    <div class="transactions-card transparent-card slide-in-right">
                        <h3>Transaction History</h3>
                        <div class="transactions-list">
                            <?php if (empty($transactions)): ?>
                                <div class="no-transactions">
                                    <i class="fas fa-history"></i>
                                    <p>No transactions yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($transactions as $transaction): ?>
                                <div class="transaction-item <?php echo $transaction['transaction_type']; ?>">
                                    <div class="transaction-icon">
                                        <i class="fas fa-<?php echo $transaction['transaction_type'] === 'earned' ? 'plus' : 'minus'; ?>"></i>
                                    </div>
                                    <div class="transaction-details">
                                        <div class="transaction-description"><?php echo htmlspecialchars($transaction['description']); ?></div>
                                        <div class="transaction-date"><?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?></div>
                                    </div>
                                    <div class="transaction-amount <?php echo $transaction['transaction_type']; ?>">
                                        <?php echo $transaction['transaction_type'] === 'earned' ? '+' : '-'; ?><?php echo $transaction['amount']; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function goToCharacterProfile() {
            window.location.href = 'character.php';
        }
    </script>
    
    <style>
        .shard-summary {
            padding: 1rem 0;
        }
        
        .shard-balance {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .balance-amount {
            font-size: 2rem;
            font-weight: bold;
            color: var(--royal-blue);
            margin-top: 0.5rem;
        }
        
        .shard-stats {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stat-label {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .stat-value {
            font-weight: bold;
            color: var(--white);
        }
        
        .transactions-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .transaction-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: background-color 0.3s ease;
        }
        
        .transaction-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .transaction-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }
        
        .transaction-item.earned .transaction-icon {
            background-color: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }
        
        .transaction-item.spent .transaction-icon {
            background-color: rgba(244, 67, 54, 0.2);
            color: #F44336;
        }
        
        .transaction-details {
            flex: 1;
        }
        
        .transaction-description {
            font-weight: 500;
            color: var(--white);
            margin-bottom: 0.25rem;
        }
        
        .transaction-date {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .transaction-amount {
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .transaction-amount.earned {
            color: #4CAF50;
        }
        
        .transaction-amount.spent {
            color: #F44336;
        }
        
        .no-transactions {
            text-align: center;
            padding: 3rem 1rem;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .no-transactions i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</body>
</html>
