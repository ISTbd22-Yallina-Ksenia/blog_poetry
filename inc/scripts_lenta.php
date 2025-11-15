    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const addPostBtn = document.getElementById('addPostBtn');
        const postModal = document.getElementById('postModal');
        const closeModal = document.getElementById('closeModal');
        const publishBtn = document.getElementById('publishBtn');
        const postText = document.getElementById('postText');
        
        // Открытие модального окна
        if (addPostBtn) {
            addPostBtn.addEventListener('click', function() {
                postModal.style.display = 'block';
                postText.focus();
            });
        }
        
        // Закрытие модального окна при клике на крестик
        if (closeModal) {
            closeModal.addEventListener('click', function() {
                postModal.style.display = 'none';
                postText.value = '';
            });
        }
        
        // Закрытие модального окна при клике вне его
        window.addEventListener('click', function(event) {
            if (event.target === postModal) {
                postModal.style.display = 'none';
                postText.value = '';
            }
        });
        
        // Обработчик публикации нового поста
        if (publishBtn) {
            publishBtn.addEventListener('click', function() {
                const text = postText.value.trim();
                if (text) {
                    savePostToDB(text);
                } else {
                    alert('Пожалуйста, введите текст поста');
                }
            });
        }
        
        // Обработчики лайков постов
        document.querySelectorAll('.like-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const postId = this.getAttribute('data-post-id');
                const isCurrentlyLiked = this.getAttribute('data-liked') === '1';
                togglePostLike(postId, this, isCurrentlyLiked);
            });
        });
        
        // Обработчики лайков комментариев
        document.querySelectorAll('.comment-like-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const commentId = this.getAttribute('data-comment-id');
                const isCurrentlyLiked = this.getAttribute('data-liked') === '1';
                toggleCommentLike(commentId, this, isCurrentlyLiked);
            });
        });
        
        // Обработчики кнопок "Ответить"
        document.querySelectorAll('.reply-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const commentId = this.getAttribute('data-comment-id');
                const replyForm = document.getElementById(`reply-form-${commentId}`);
                replyForm.style.display = replyForm.style.display === 'block' ? 'none' : 'block';
            });
        });
        
        // Обработчики отправки комментариев
        document.querySelectorAll('.comment-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const postId = this.getAttribute('data-post-id');
                const commentInput = document.getElementById(`comment-input-${postId}`);
                const text = commentInput.value.trim();
                
                if (text) {
                    saveCommentToDB(postId, text, commentInput);
                }
            });
        });
        
        // Обработчики отправки ответов
        document.querySelectorAll('.reply-submit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const parentCommentId = this.getAttribute('data-comment-id');
                const replyInput = document.getElementById(`reply-input-${parentCommentId}`);
                const text = replyInput.value.trim();
                
                if (text) {
                    saveReplyToDB(parentCommentId, text, replyInput);
                }
            });
        });
        
        // Функция сохранения поста в базу данных
        function savePostToDB(text) {
            const formData = new FormData();
            formData.append('text', text);
            
            fetch('save_post.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    postText.value = '';
                    postModal.style.display = 'none';
                    location.reload();
                } else {
                    alert('Ошибка: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Ошибка при публикации поста');
            });
        }
        
        // Функция для переключения лайка поста
        function togglePostLike(postId, likeButton, isCurrentlyLiked) {
            const formData = new FormData();
            formData.append('post_id', postId);
            formData.append('action', isCurrentlyLiked ? 'unlike' : 'like');
            
            fetch('like_post.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    const likeCount = likeButton.querySelector('.like-count');
                    likeCount.textContent = result.likes;
                    
                    if (result.action === 'liked') {
                        likeButton.classList.add('liked');
                        likeButton.setAttribute('data-liked', '1');
                    } else {
                        likeButton.classList.remove('liked');
                        likeButton.setAttribute('data-liked', '0');
                    }
                } else {
                    alert('Ошибка: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Ошибка при обработке лайка');
            });
        }
        
        // Функция для переключения лайка комментария
        function toggleCommentLike(commentId, likeButton, isCurrentlyLiked) {
            const formData = new FormData();
            formData.append('comment_id', commentId);
            formData.append('action', isCurrentlyLiked ? 'unlike' : 'like');
            
            fetch('like_comment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    const likeCount = likeButton.querySelector('.comment-like-count');
                    likeCount.textContent = result.likes;
                    
                    if (result.action === 'liked') {
                        likeButton.classList.add('liked');
                        likeButton.setAttribute('data-liked', '1');
                    } else {
                        likeButton.classList.remove('liked');
                        likeButton.setAttribute('data-liked', '0');
                    }
                } else {
                    alert('Ошибка: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Ошибка при обработке лайка комментария');
            });
        }
        
        // Функция сохранения комментария в БД
        function saveCommentToDB(postId, text, commentInput) {
            const formData = new FormData();
            formData.append('post_id', postId);
            formData.append('text', text);
            
            fetch('save_comment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    commentInput.value = '';
                    location.reload();
                } else {
                    alert('Ошибка: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
            });
        }
        
        // Функция сохранения ответа на комментарий
        function saveReplyToDB(parentCommentId, text, replyInput) {
            const formData = new FormData();
            formData.append('parent_comment_id', parentCommentId);
            formData.append('text', text);
            
            fetch('save_comment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    replyInput.value = '';
                    document.getElementById(`reply-form-${parentCommentId}`).style.display = 'none';
                    location.reload();
                } else {
                    alert('Ошибка: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
            });
        }
       
    });
    </script>
    <script>
    // Автоматическое изменение высоты textarea при вводе
function autoResizeTextarea(textarea) {
    // Сбрасываем высоту до минимальной
    textarea.style.height = 'auto';
    
    // Устанавливаем новую высоту на основе scrollHeight
    // Добавляем небольшой запас (обычно 2-4px) для корректного отображения
    const newHeight = Math.min(textarea.scrollHeight + 2, 120); // Ограничиваем максимальной высотой
    
    textarea.style.height = newHeight + 'px';
    textarea.style.overflowY = newHeight >= 120 ? 'auto' : 'hidden'; // Показываем скролл только при максимальной высоте
}

// Применяем ко всем textarea комментариев после загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.comment-input textarea').forEach(textarea => {
        // Инициализируем начальную высоту
        autoResizeTextarea(textarea);
        
        // Обработчик ввода текста
        textarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
        
        // Обработчик изменения размера (на всякий случай)
        textarea.addEventListener('change', function() {
            autoResizeTextarea(this);
        });
    });
});
    </script>