// Student filtering functionality
const studentSearch = document.getElementById('studentSearch');
const gradeFilter = document.getElementById('gradeFilter');

if (studentSearch || gradeFilter) {
    function filterStudents() {
        // Get values
        const searchTerm = studentSearch ? studentSearch.value.toLowerCase() : '';
        const selectedGrade = gradeFilter ? gradeFilter.value : 'all';
        
        const rows = document.querySelectorAll('#studentsTbody tr');
        
        rows.forEach(row => {
            if (row.querySelector('.no-students')) return;
            
            // Get data from columns
            const name = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
            const gradeLevel = row.querySelector('td:nth-child(2)').textContent.trim(); // Case sensitive match for exact grade
            const section = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const email = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
            
            // Check matches
            const matchesSearch = name.includes(searchTerm) || 
                                  gradeLevel.toLowerCase().includes(searchTerm) || 
                                  section.includes(searchTerm) || 
                                  email.includes(searchTerm);
                                  
            const matchesGrade = selectedGrade === 'all' || gradeLevel === selectedGrade;
            
            if (matchesSearch && matchesGrade) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Handle "No Result" message
        updateNoResultsMessage(searchTerm, selectedGrade);
    }
    
    function updateNoResultsMessage(searchTerm, selectedGrade) {
        const tbody = document.getElementById('studentsTbody');
        if (!tbody) return;
        
        const rows = tbody.querySelectorAll('tr:not(.no-results)');
        const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
        let noResultsRow = tbody.querySelector('.no-results');
        
        if (visibleRows.length === 0) {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.className = 'no-results';
                noResultsRow.innerHTML = '<td colspan="5" class="no-students">No students found matching your criteria</td>';
                tbody.appendChild(noResultsRow);
            }
        } else if (noResultsRow) {
            noResultsRow.remove();
        }
    }

    // Add event listeners
    if (studentSearch) studentSearch.addEventListener('input', filterStudents);
    if (gradeFilter) gradeFilter.addEventListener('change', filterStudents);
}

// Vocabulary search functionality
// Vocabulary filtering functionality
const vocabSearch = document.getElementById('vocabSearch');
const gradeFilterVocab = document.getElementById('gradeFilterVocab');
const difficultyFilter = document.getElementById('difficultyFilter');

if (vocabSearch || gradeFilterVocab || difficultyFilter) {
    function filterVocabulary() {
        const searchTerm = vocabSearch ? vocabSearch.value.toLowerCase() : '';
        const selectedGrade = gradeFilterVocab ? gradeFilterVocab.value : 'all';
        const selectedDifficulty = difficultyFilter ? difficultyFilter.value : 'all';

        const rows = document.querySelectorAll('#vocabTbody tr');

        rows.forEach(row => {
            if (row.querySelector('.no-vocab')) return;

            const word = (row.dataset.word || '').toLowerCase();
            const definition = (row.dataset.definition || '').toLowerCase();
            const grade = row.dataset.grade || '';
            const difficulty = row.dataset.difficulty || '';

            const matchesSearch = word.includes(searchTerm) || definition.includes(searchTerm);
            const matchesGrade = selectedGrade === 'all' || grade === selectedGrade;
            const matchesDifficulty = selectedDifficulty === 'all' || difficulty === selectedDifficulty;

            if (matchesSearch && matchesGrade && matchesDifficulty) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Handle "No Result" message logic could be added here similar to students
    }

    if (vocabSearch) vocabSearch.addEventListener('input', filterVocabulary);
    if (gradeFilterVocab) gradeFilterVocab.addEventListener('change', filterVocabulary);
    if (difficultyFilter) difficultyFilter.addEventListener('change', filterVocabulary);
}

// Table sorting functionality
// Table sorting functionality
// Table sorting functionality
document.querySelectorAll('.sortable').forEach(header => {
    header.addEventListener('click', function() {
        const table = this.closest('table');
        const tbody = table.querySelector('tbody');
        const sortKey = this.dataset.sort;
        const currentOrder = this.dataset.order || 'asc';
        const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
        const columnIndex = this.cellIndex;
        
        // Update data-order for next click
        this.dataset.order = newOrder;
        
        // Update visual indicators
        table.querySelectorAll('.sortable').forEach(h => {
            h.innerHTML = h.textContent.replace(' ↑', '').replace(' ↓', '').trim();
        });
        this.innerHTML = `${this.textContent.trim()} ${newOrder === 'asc' ? '↑' : '↓'}`;
        
        const rows = Array.from(tbody.querySelectorAll('tr:not(.no-results):not(.no-vocab)'));
        
        rows.sort((a, b) => {
            const aCell = a.cells[columnIndex];
            const bCell = b.cells[columnIndex];
            
            const aVal = aCell ? aCell.textContent.trim().toLowerCase() : '';
            const bVal = bCell ? bCell.textContent.trim().toLowerCase() : '';
            
            // Date sorting specific for students table
            if (sortKey === 'created_at') {
                const aDate = new Date(aVal);
                const bDate = new Date(bVal);
                return newOrder === 'asc' ? aDate - bDate : bDate - aDate;
            }
            
            if (aVal < bVal) return newOrder === 'asc' ? -1 : 1;
            if (aVal > bVal) return newOrder === 'asc' ? 1 : -1;
            return 0;
        });
        
        // Re-append rows in sorted order
        rows.forEach(row => tbody.appendChild(row));
    });
});

// Mobile menu functionality
const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
const sidebar = document.querySelector('.sidebar');

if (mobileMenuBtn && sidebar) {
    mobileMenuBtn.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });
}

// ============================================
// VOCABULARY CRUD OPERATIONS
// ============================================

let currentVocabId = null;
let deleteVocabId = null;

// Open Add Modal
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Vocabulary';
    document.getElementById('vocabForm').reset();
    document.getElementById('vocabId').value = '';
    currentVocabId = null;
    document.getElementById('vocabModal').classList.add('show');
}

// Open Edit Modal
async function editVocab(vocabId) {
    try {
        const response = await fetch(`api/vocabulary_crud.php?action=get&vocab_id=${vocabId}`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('modalTitle').textContent = 'Edit Vocabulary';
            document.getElementById('vocabId').value = data.question.id;
            document.getElementById('word').value = data.question.word;
            document.getElementById('definition').value = data.question.definition;
            document.getElementById('example').value = data.question.example_sentence || '';
            document.getElementById('gradeLevel').value = data.question.grade_level;
            document.getElementById('difficulty').value = data.question.difficulty;
            
            // Fill choices
            data.choices.forEach((choice, index) => {
                document.getElementById(`choice${index}`).value = choice.choice_text;
                if (choice.is_correct == 1) {
                    document.getElementById(`correct${index}`).checked = true;
                }
            });
            
            currentVocabId = vocabId;
            document.getElementById('vocabModal').classList.add('show');
        } else {
            alert('Error loading vocabulary: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error loading vocabulary');
    }
}

// Close Vocab Modal
function closeVocabModal() {
    document.getElementById('vocabModal').classList.remove('show');
    document.getElementById('vocabForm').reset();
    currentVocabId = null;
}

// Save Vocabulary (Add or Edit)
async function saveVocab(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const action = currentVocabId ? 'edit' : 'add';
    formData.append('action', action);
    
    if (currentVocabId) {
        formData.set('vocab_id', currentVocabId);
    }
    
    try {
        const response = await fetch('api/vocabulary_crud.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            closeVocabModal();
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error saving vocabulary');
    }
}

// Delete Vocabulary
function deleteVocab(vocabId, wordName) {
    deleteVocabId = vocabId;
    document.getElementById('deleteWordName').textContent = wordName;
    document.getElementById('deleteModal').classList.add('show');
}

// Close Delete Modal
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    deleteVocabId = null;
}

// Confirm Delete
async function confirmDelete() {
    if (!deleteVocabId) return;
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('vocab_id', deleteVocabId);
    
    try {
        const response = await fetch('api/vocabulary_crud.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            closeDeleteModal();
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error deleting vocabulary');
    }
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        if (e.target.id === 'vocabModal') {
            closeVocabModal();
        } else if (e.target.id === 'deleteModal') {
            closeDeleteModal();
        }
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // ESC key closes modals
    if (e.key === 'Escape') {
        closeVocabModal();
        closeDeleteModal();
    }
});
