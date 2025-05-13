<?php
require_once __DIR__ . '/../db.php';

// Get all communities
function getAllCommunities() {
    global $conn;
    
    try {
        $sql = "SELECT c.*, 
                (SELECT COUNT(*) FROM forum_topics WHERE community_id = c.id) AS topic_count,
                u.username as creator
                FROM forum_communities c
                LEFT JOIN users u ON c.user_id = u.id
                ORDER BY c.name ASC";
        $result = $conn->query($sql);
        
        $communities = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $communities[] = $row;
            }
        }
        
        return $communities;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting communities: " . $e->getMessage());
        return [];
    }
}

// Get a specific community by slug
function getCommunityBySlug($slug) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM forum_communities WHERE slug = ?");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $community = $result->fetch_assoc();
        $stmt->close();
        
        return $community;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting community: " . $e->getMessage());
        return null;
    }
}

// Get a specific community by ID
function getCommunity($community_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM forum_communities WHERE id = ?");
        $stmt->bind_param("i", $community_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $community = $result->fetch_assoc();
        $stmt->close();
        
        return $community;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting community: " . $e->getMessage());
        return null;
    }
}

// Get topics for a specific community with pagination
function getTopics($community_id, $page = 1, $per_page = 10) {
    global $conn;
    
    try {
        // Calculate offset
        $offset = ($page - 1) * $per_page;
        
        // Get topics with username and vote count
        $sql = "SELECT t.*, u.username, 
                (SELECT COUNT(*) FROM forum_comments WHERE topic_id = t.id) AS comment_count,
                COALESCE((SELECT SUM(vote_type) FROM forum_votes WHERE reference_id = t.id AND reference_type = 'topic'), 0) AS vote_score
                FROM forum_topics t
                LEFT JOIN users u ON t.user_id = u.id
                WHERE t.community_id = ?
                ORDER BY t.created_at DESC
                LIMIT ?, ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $community_id, $offset, $per_page);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $topics = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $topics[] = $row;
            }
        }
        $stmt->close();
        
        return $topics;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting topics: " . $e->getMessage());
        return [];
    }
}

// Get total number of topics in a community
function getTopicCount($community_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM forum_topics WHERE community_id = ?");
        $stmt->bind_param("i", $community_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $stmt->close();
        
        return $count;
    } catch (mysqli_sql_exception $e) {
        error_log("Error counting topics: " . $e->getMessage());
        return 0;
    }
}

// Get a specific topic with related information
function getTopic($topic_id) {
    global $conn;
    
    try {
        // Increment view count
        $update = $conn->prepare("UPDATE forum_topics SET views = views + 1 WHERE id = ?");
        $update->bind_param("i", $topic_id);
        $update->execute();
        $update->close();
        
        // Récupérer d'abord les données du sujet sans les jointures pour s'assurer qu'il existe
        $stmt = $conn->prepare("SELECT * FROM forum_topics WHERE id = ?");
        $stmt->bind_param("i", $topic_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $stmt->close();
            return null; // Le sujet n'existe pas
        }
        
        $basic_topic = $result->fetch_assoc();
        $stmt->close();
        
        // Initialiser le sujet avec des valeurs par défaut
        $topic = $basic_topic;
        $topic['username'] = 'Anonyme';
        $topic['vote_score'] = 0;
        $topic['community_id'] = $topic['community_id'] ?? 0;
        $topic['community_name'] = 'Unknown Community';
        $topic['community_slug'] = 'unknown';
        
        // Maintenant, récupérer les informations supplémentaires
        $sql = "SELECT t.*, u.username, c.name as community_name, c.slug as community_slug, 
                (SELECT COUNT(*) FROM forum_views WHERE topic_id = t.id) as views,
                COALESCE((SELECT SUM(vote_type) FROM forum_votes WHERE reference_id = t.id AND reference_type = 'topic'), 0) AS vote_score,
                t.image_url
                FROM forum_topics t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN forum_communities c ON t.community_id = c.id
                WHERE t.id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $topic_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            if (!empty($user_data['username'])) {
                $topic['username'] = $user_data['username'];
            }
            if (!empty($user_data['profile_picture'])) {
                $topic['profile_picture'] = $user_data['profile_picture'];
            }
        }
        $stmt->close();
        
        // Ajouter le score de vote
        $vote_stmt = $conn->prepare("SELECT COALESCE(SUM(vote_type), 0) AS vote_score 
                                    FROM forum_votes 
                                    WHERE reference_id = ? AND reference_type = 'topic'");
        $vote_stmt->bind_param("i", $topic_id);
        $vote_stmt->execute();
        $vote_result = $vote_stmt->get_result();
        
        if ($vote_result && $vote_result->num_rows > 0) {
            $vote_data = $vote_result->fetch_assoc();
            $topic['vote_score'] = intval($vote_data['vote_score']);
        } else {
            $topic['vote_score'] = 0;
        }
        $vote_stmt->close();
        
        // Récupérer les informations de la communauté séparément
        if (isset($topic['community_id']) && $topic['community_id'] > 0) {
            $community_stmt = $conn->prepare("SELECT id, name, slug FROM forum_communities WHERE id = ?");
            $community_stmt->bind_param("i", $topic['community_id']);
            $community_stmt->execute();
            $community_result = $community_stmt->get_result();
            
            if ($community_result->num_rows > 0) {
                $community = $community_result->fetch_assoc();
                $topic['community_name'] = $community['name'];
                $topic['community_slug'] = $community['slug'];
            }
            $community_stmt->close();
        }
        
        if (!$topic) {
            $topic = [
                'id' => 0,
                'title' => '',
                'content' => '',
                'user_id' => 0,
                'created_at' => '',
                'community_id' => 0,
                'community_name' => 'Unknown Community',
                'community_slug' => 'unknown'
            ];
        }
        
        return $topic;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting topic: " . $e->getMessage());
        // En cas d'erreur, retourner les données de base du sujet si disponibles
        if (isset($basic_topic)) {
            $basic_topic['vote_score'] = 0;
            $basic_topic['username'] = 'Anonyme';
            $basic_topic['community_name'] = 'Unknown Community';
            $basic_topic['community_slug'] = 'unknown';
            return $basic_topic;
        }
        return null;
    }
}

// Get comments for a specific topic with pagination
function getComments($topic_id, $page = 1, $per_page = 20, $sort_by = 'date') {
    global $conn;
    
    try {
        // Activer l'affichage des erreurs pour le débogage
        error_log("Fetching comments for topic_id: " . $topic_id);
        
        // Vérifier d'abord si le topic existe
        $topic_check = $conn->prepare("SELECT id FROM forum_topics WHERE id = ?");
        $topic_check->bind_param("i", $topic_id);
        $topic_check->execute();
        $topic_result = $topic_check->get_result();
        
        if ($topic_result->num_rows === 0) {
            error_log("Topic not found: " . $topic_id);
            return [];
        }
        $topic_check->close();
        
        // Calculate offset
        $offset = ($page - 1) * $per_page;
        
        // Determine sorting order
        $order_by = ($sort_by == 'votes') 
            ? "c.vote_score DESC, c.created_at DESC" 
            : "c.created_at ASC";
            
        // Get parent comments (comments without parent_id)
        $sql = "SELECT c.*, COALESCE(u.username, 'Anonymous') as username,
                COALESCE((
                    SELECT SUM(vote_type) 
                    FROM forum_votes 
                    WHERE reference_id = c.id AND reference_type = 'comment'
                ), 0) as vote_score
                FROM forum_comments c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.topic_id = ? AND (c.parent_id IS NULL OR c.parent_id = 0)
                ORDER BY $order_by
                LIMIT ?, ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $topic_id, $offset, $per_page);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $row['id'] = intval($row['id']);
            $row['topic_id'] = intval($row['topic_id']);
            $row['user_id'] = intval($row['user_id']);
            $row['vote_score'] = intval($row['vote_score']);
            
            // Get all replies recursively
            $row['replies'] = getNestedReplies($row['id'], $topic_id);
            
            $comments[] = $row;
        }
        
        $stmt->close();
        return $comments;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting comments: " . $e->getMessage());
        return [];
    }
}

// Get replies recursively
function getNestedReplies($parent_id, $topic_id) {
    global $conn;
    
    // Get all direct replies to this comment
    $sql = "SELECT c.*, COALESCE(u.username, 'Anonymous') as username,
            COALESCE((
                SELECT SUM(vote_type) 
                FROM forum_votes 
                WHERE reference_id = c.id AND reference_type = 'comment'
            ), 0) as vote_score
            FROM forum_comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.parent_id = ? AND c.topic_id = ?
            ORDER BY c.created_at ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $parent_id, $topic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $replies = [];
    while ($reply = $result->fetch_assoc()) {
        // Get replies to this reply recursively
        $reply['replies'] = getNestedReplies($reply['id'], $topic_id);
        $replies[] = $reply;
    }
    
    return $replies;
}

// Get total number of comments for a topic
function getCommentCount($topic_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM forum_comments WHERE topic_id = ? AND parent_id IS NULL");
        $stmt->bind_param("i", $topic_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $stmt->close();
        
        return $count;
    } catch (mysqli_sql_exception $e) {
        error_log("Error counting comments: " . $e->getMessage());
        return 0;
    }
}

// Create a new topic
function createTopic($community_id, $user_id, $title, $content) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("CALL InsertForumTopic(?, ?, ?, ?)");
        $stmt->bind_param("iiss", $community_id, $user_id, $title, $content);
        $stmt->execute();
        $topic_id = $conn->insert_id;
        $stmt->close();
        
        return $topic_id;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating topic: " . $e->getMessage());
        return 0;
    }
}

// Add a comment to a topic
function addComment($topic_id, $user_id, $content, $parent_id = null) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO forum_comments (topic_id, user_id, content, parent_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iisi", $topic_id, $user_id, $content, $parent_id);
        $success = $stmt->execute();
        $comment_id = $success ? $conn->insert_id : 0;
        $stmt->close();
        
        return $comment_id;
    } catch (mysqli_sql_exception $e) {
        error_log("Error adding comment: " . $e->getMessage());
        return 0;
    }
}

// Vote on a topic or comment
function voteOnItem($user_id, $reference_id, $reference_type, $vote_type) {
    global $conn;
    
    try {
        // Vérifier si l'utilisateur a déjà voté sur cet élément
        $check = $conn->prepare("SELECT id, vote_type FROM forum_votes WHERE user_id = ? AND reference_id = ? AND reference_type = ?");
        $check->bind_param("iis", $user_id, $reference_id, $reference_type);
        $check->execute();
        $existing = $check->get_result();
        $check->close();
        
        if ($existing->num_rows > 0) {
            $row = $existing->fetch_assoc();
            // Si l'utilisateur clique sur le même bouton, on supprime le vote
            if ($row['vote_type'] == $vote_type) {
                $stmt = $conn->prepare("DELETE FROM forum_votes WHERE id = ?");
                $stmt->bind_param("i", $row['id']);
                $stmt->execute();
                $stmt->close();
            } else {
                // Si l'utilisateur change son vote, on met à jour
                $stmt = $conn->prepare("UPDATE forum_votes SET vote_type = ? WHERE id = ?");
                $stmt->bind_param("ii", $vote_type, $row['id']);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            // Vérifier une seconde fois avant d'insérer pour éviter les votes multiples
            $double_check = $conn->prepare("SELECT id FROM forum_votes WHERE user_id = ? AND reference_id = ? AND reference_type = ?");
            $double_check->bind_param("iis", $user_id, $reference_id, $reference_type);
            $double_check->execute();
            $double_result = $double_check->get_result();
            $double_check->close();
            
            if ($double_result->num_rows == 0) {
                // Insert new vote only if no vote exists
                $stmt = $conn->prepare("INSERT INTO forum_votes (user_id, reference_id, reference_type, vote_type) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iisi", $user_id, $reference_id, $reference_type, $vote_type);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        // Recalculate and return the new vote score
        $score_stmt = $conn->prepare("SELECT COALESCE(SUM(vote_type), 0) as score FROM forum_votes WHERE reference_id = ? AND reference_type = ?");
        $score_stmt->bind_param("is", $reference_id, $reference_type);
        $score_stmt->execute();
        $score_result = $score_stmt->get_result();
        $score = 0;
        if ($score_result && $score_result->num_rows > 0) {
            $score_data = $score_result->fetch_assoc();
            $score = intval($score_data['score']);
        }
        $score_stmt->close();
        
        // Mettre à jour le score dans la base de données selon le type
        if ($reference_type === 'topic') {
            $update_stmt = $conn->prepare("UPDATE forum_topics SET vote_score = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $score, $reference_id);
            $update_stmt->execute();
            $update_stmt->close();
        } elseif ($reference_type === 'comment') {
            $update_stmt = $conn->prepare("UPDATE forum_comments SET vote_score = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $score, $reference_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
        
        return $score;
    } catch (mysqli_sql_exception $e) {
        error_log("Error voting on item: " . $e->getMessage());
        return 0;
    }
}

// Get current user's vote for an item
function getUserVote($user_id, $reference_id, $reference_type) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT vote_type FROM forum_votes WHERE user_id = ? AND reference_id = ? AND reference_type = ?");
        $stmt->bind_param("iis", $user_id, $reference_id, $reference_type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $vote = intval($row['vote_type']);
        } else {
            $vote = 0; // No vote
        }
        
        $stmt->close();
        
        // S'assurer que la valeur retournée est -1, 0 ou 1
        if ($vote > 0) return 1;
        if ($vote < 0) return -1;
        return 0;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting user vote: " . $e->getMessage());
        return 0;
    }
}

// Get trending/hot topics
function getTrendingTopics($limit = 5) {
    global $conn;
    
    try {
        $sql = "SELECT t.*, u.username, c.name as community_name, c.slug as community_slug,
                (SELECT COUNT(*) FROM forum_comments WHERE topic_id = t.id) AS comment_count,
                COALESCE((SELECT SUM(vote_type) FROM forum_votes WHERE reference_id = t.id AND reference_type = 'topic'), 0) AS vote_score,
                t.image_url
                FROM forum_topics t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN forum_communities c ON t.community_id = c.id
                ORDER BY (vote_score * 10 + comment_count * 5 + views) DESC, t.created_at DESC
                LIMIT ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $topics = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $topics[] = $row;
            }
        }
        $stmt->close();
        
        return $topics;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting trending topics: " . $e->getMessage());
        return [];
    }
}

// Format timestamp to readable date
function formatTimestamp($timestamp) {
    $date = new DateTime($timestamp);
    $now = new DateTime();
    $diff = $now->diff($date);
      if ($diff->y > 0) {
        return $date->format('j M Y');
    } elseif ($diff->m > 0) {
        return 'il y a ' . $diff->m . ' mois';
    } elseif ($diff->d > 6) {
        return $date->format('j M');
    } elseif ($diff->d > 0) {
        return 'il y a ' . $diff->d . ' jour' . ($diff->d > 1 ? 's' : '');
    } elseif ($diff->h > 0) {
        return 'il y a ' . $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
    } elseif ($diff->i > 0) {
        return 'il y a ' . $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
    } elseif ($diff->s > 30) {
        return 'il y a ' . $diff->s . ' secondes';
    } else {
        return 'à l\'instant';
    }
}

// Delete comment and its associated data
function deleteComment($comment_id, $user_id, $is_admin = false) {
    global $conn;
    
    // First delete associated votes
    $sql = "DELETE FROM forum_votes WHERE reference_id = ? AND reference_type = 'comment'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    
    // Delete replies recursively
    $sql = "SELECT id FROM forum_comments WHERE parent_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($reply = $result->fetch_assoc()) {
        deleteComment($reply['id'], $user_id, $is_admin);
    }
    
    // Delete the comment
    $sql = "DELETE FROM forum_comments WHERE id = ? AND (user_id = ? OR ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $comment_id, $user_id, $is_admin);
    return $stmt->execute();
}

function updateComment($comment_id, $content, $user_id, $is_admin = false) {
    global $conn;
    
    try {
        // Vérifier si l'utilisateur est l'auteur du commentaire ou un admin
        if (!$is_admin) {
            $check = $conn->prepare("SELECT user_id FROM forum_comments WHERE id = ?");
            $check->bind_param("i", $comment_id);
            $check->execute();
            $result = $check->get_result();
            $comment = $result->fetch_assoc();
            $check->close();
            
            if (!$comment || $comment['user_id'] !== $user_id) {
                return false;
            }
        }
        
        // Mettre à jour le commentaire
        $stmt = $conn->prepare("UPDATE forum_comments SET content = ?, edited_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $content, $comment_id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Error updating comment: " . $e->getMessage());
        return false;
    }
}

// Delete topic and all associated data
function deleteTopic($topic_id, $user_id, $is_admin = false) {
    global $conn;
    
    // Delete all comments and their votes
    $sql = "SELECT id FROM forum_comments WHERE topic_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $topic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($comment = $result->fetch_assoc()) {
        deleteComment($comment['id'], $user_id, $is_admin);
    }
    
    // Delete topic votes
    $sql = "DELETE FROM forum_votes WHERE reference_id = ? AND reference_type = 'topic'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $topic_id);
    $stmt->execute();
    
    // Delete topic
    $sql = "DELETE FROM forum_topics WHERE id = ? AND (user_id = ? OR ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $topic_id, $user_id, $is_admin);
    return $stmt->execute();
}

function updateTopic($topic_id, $title, $content, $user_id, $is_admin = false) {
    global $conn;
    
    try {
        // Vérifier si l'utilisateur est l'auteur du topic ou un admin
        if (!$is_admin) {
            $check = $conn->prepare("SELECT user_id FROM forum_topics WHERE id = ?");
            $check->bind_param("i", $topic_id);
            $check->execute();
            $result = $check->get_result();
            $topic = $result->fetch_assoc();
            $check->close();
            
            if (!$topic || $topic['user_id'] !== $user_id) {
                return false;
            }
        }
        
        // Mettre à jour le topic
        $stmt = $conn->prepare("UPDATE forum_topics SET title = ?, content = ?, edited_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssi", $title, $content, $topic_id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Error updating topic: " . $e->getMessage());
        return false;
    }
}
?>