// Глобальные переменные
let applications = JSON.parse(localStorage.getItem('applications')) || [];
let users = JSON.parse(localStorage.getItem('users')) || [];
let captainReports = JSON.parse(localStorage.getItem('captainReports')) || [];
let captainElements = JSON.parse(localStorage.getItem('captainElements')) || []; // Новая переменная
let currentApplication = null;
let currentCaptainReport = null;
let currentCaptainElement = null; // Новая переменная
let adminSettings = JSON.parse(localStorage.getItem('adminSettings')) || getDefaultSettings();
let sidebarCollapsed = false;
let refreshInterval;

// Проверка на админа (для демонстрации используем простую проверку)
const ADMIN_LOGIN = 'admin';
const ADMIN_PASSWORD = 'admin123';

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    checkAdminAuth();
    initializeAdmin();
});

function checkAdminAuth() {
    const adminAuth = localStorage.getItem('adminAuth');
    if (!adminAuth) {
        const login = prompt('Введите логин администратора:');
        const password = prompt('Введите пароль администратора:');
        
        if (login !== ADMIN_LOGIN || password !== ADMIN_PASSWORD) {
            alert('Неверные данные администратора');
            window.location.href = 'index.html';
            return;
        }
        
        localStorage.setItem('adminAuth', 'true');
    }
}

function getDefaultSettings() {
    return {
        autoApproval: 'off',
        dailyLimit: 50,
        emailNotifications: 'off',
        welcomeMessage: 'Добро пожаловать в систему VOID',
        systemAnnouncements: 'Система работает в штатном режиме',
        maintenanceMode: 'off',
        maxUsers: 1000
    };
}

function initializeAdmin() {
    loadStatistics();
    loadApplications();
    loadUsers();
    loadCaptainReports();
    loadCaptainElements(); // Новая функция
    loadSettings();
    loadServerData();
    generateLogs();
    
    // Запуск автообновления
    startAutoRefresh();
    
    // Автообновление каждые 30 секунд
    setInterval(() => {
        loadStatistics();
        updateSystemMetrics();
        if (document.getElementById('applications-section').classList.contains('active')) {
            loadApplications();
        }
        if (document.getElementById('captains-section').classList.contains('active')) {
            loadCaptainReports();
        }
        if (document.getElementById('captain-elements-section').classList.contains('active')) {
            loadCaptainElements();
        }
    }, 30000);
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContainer = document.getElementById('mainContainer');
    
    sidebarCollapsed = !sidebarCollapsed;
    
    if (sidebarCollapsed) {
        sidebar.classList.add('collapsed');
        mainContainer.classList.add('expanded');
    } else {
        sidebar.classList.remove('collapsed');
        mainContainer.classList.remove('expanded');
    }

    // На мобильных устройствах
    if (window.innerWidth <= 768) {
        if (sidebarCollapsed) {
            sidebar.classList.remove('open');
        } else {
            sidebar.classList.add('open');
        }
    }
}

function showSection(sectionId) {
    // Скрыть все секции
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => section.classList.remove('active'));
    
    // Убрать активный класс с навигации
    const navItems = document.querySelectorAll('.menu-item');
    navItems.forEach(item => item.classList.remove('active'));
    
    // Показать выбранную секцию
    document.getElementById(sectionId + '-section').classList.add('active');
    document.getElementById(sectionId + '-menu').classList.add('active');
    
    // Закрыть боковое меню на мобильных
    if (window.innerWidth <= 768) {
        toggleSidebar();
    }
    
    // Загрузить данные при необходимости
    if (sectionId === 'applications') {
        loadApplications();
    } else if (sectionId === 'users') {
        loadUsers();
    } else if (sectionId === 'captains') {
        loadCaptainReports();
    } else if (sectionId === 'captain-elements') {
        loadCaptainElements();
    } else if (sectionId === 'servers') {
        loadServerData();
    } else if (sectionId === 'logs') {
        generateLogs();
    }
}

function loadStatistics() {
    // Обновление данных из localStorage
    applications = JSON.parse(localStorage.getItem('applications')) || [];
    users = JSON.parse(localStorage.getItem('users')) || [];
    captainReports = JSON.parse(localStorage.getItem('captainReports')) || [];
    
    const pendingApps = applications.filter(app => app.status === 'pending');
    const pendingCaptainReportsCount = captainReports.filter(report => report.status === 'pending').length;
    
    document.getElementById('pendingCount').textContent = pendingApps.length;
    document.getElementById('approvedCount').textContent = users.length;
    document.getElementById('rejectedCount').textContent = applications.filter(app => app.status === 'rejected').length;
    document.getElementById('pendingCaptainReports').textContent = pendingCaptainReportsCount;
    
    // Обновление основной статистики
    const activeUsersElement = document.getElementById('activeUsers');
    const serversOnlineElement = document.getElementById('serversOnline');
    const systemLoadElement = document.getElementById('systemLoad');
    
    if (activeUsersElement) activeUsersElement.textContent = users.length;
    if (serversOnlineElement) serversOnlineElement.textContent = '12/15';
    if (systemLoadElement) systemLoadElement.textContent = '2.1';
    
    // Обновление изменений (симуляция)
    document.getElementById('pendingChange').textContent = `+${Math.floor(Math.random() * 5)} сегодня`;
    document.getElementById('approvedChange').textContent = `+${Math.floor(Math.random() * 10)} за неделю`;
    document.getElementById('rejectedChange').textContent = `+${Math.floor(Math.random() * 3)} за день`;
}

function updateSystemMetrics() {
    const cpuUsage = Math.floor(Math.random() * 30 + 20) + '%';
    const memoryUsage = Math.floor(Math.random() * 40 + 30) + '%';
    const networkIO = Math.floor(Math.random() * 100 + 50) + ' MB/s';
    const diskUsage = Math.floor(Math.random() * 50 + 30) + '%';
    
    const cpuElement = document.getElementById('cpuUsage');
    const memoryElement = document.getElementById('memoryUsage');
    const networkElement = document.getElementById('networkIO');
    const diskElement = document.getElementById('diskUsage');
    
    if (cpuElement) cpuElement.textContent = cpuUsage;
    if (memoryElement) memoryElement.textContent = memoryUsage;
    if (networkElement) networkElement.textContent = networkIO;
    if (diskElement) diskElement.textContent = diskUsage;
}

// НОВЫЕ ФУНКЦИИ ДЛЯ УПРАВЛЕНИЯ ЭЛЕМЕНТАМИ КАПТОВ

function loadCaptainElements() {
    captainElements = JSON.parse(localStorage.getItem('captainElements')) || [];
    const tableBody = document.getElementById('captainElementsTableBody');
    
    if (!tableBody) return;
    
    if (captainElements.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="no-data">Нет элементов для каптов</td>
            </tr>
        `;
        return;
    }
    
    // Сортировка элементов по порядку
    const sortedElements = [...captainElements].sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
    
    tableBody.innerHTML = sortedElements.map(element => `
        <tr>
            <td><strong>${element.id}</strong></td>
            <td>${element.title}</td>
            <td>
                ${element.image_url ? 
                    `<img src="${element.image_url}" alt="${element.title}" style="width: 50px; height: 30px; object-fit: cover; border-radius: 2px;">` : 
                    'Нет изображения'
                }
            </td>
            <td>${element.button_count || 0}</td>
            <td>${element.sort_order || 0}</td>
            <td><span class="status-badge ${element.status ? 'approved' : 'rejected'}">${element.status ? 'Активен' : 'Неактивен'}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn view-btn" onclick="editCaptainElement(${element.id})">Редактировать</button>
                    <button class="action-btn reject-btn" onclick="deleteCaptainElement(${element.id})">Удалить</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function showAddElementModal() {
    currentCaptainElement = null;
    document.getElementById('elementModalTitle').textContent = 'Добавить элемент';
    clearCaptainElementForm();
    addButtonRow(); // Добавить одну кнопку по умолчанию
    document.getElementById('captainElementModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function editCaptainElement(elementId) {
    const element = captainElements.find(el => el.id === elementId);
    if (!element) return;
    
    currentCaptainElement = element;
    document.getElementById('elementModalTitle').textContent = 'Редактировать элемент';
    
    // Заполнение формы
    document.getElementById('elementTitle').value = element.title || '';
    document.getElementById('elementImageUrl').value = element.image_url || '';
    document.getElementById('elementSortOrder').value = element.sort_order || 0;
    document.getElementById('elementStatus').value = element.status ? '1' : '0';
    
    // Загрузка кнопок элемента
    clearButtonsContainer();
    const elementButtons = JSON.parse(localStorage.getItem('captainElementButtons')) || [];
    const buttons = elementButtons.filter(btn => btn.element_id === elementId);
    
    if (buttons.length > 0) {
        buttons.forEach(button => {
            addButtonRow(button.button_url);
        });
    } else {
        addButtonRow(); // Добавить одну пустую кнопку
    }
    
    document.getElementById('captainElementModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function deleteCaptainElement(elementId) {
    if (!confirm('Удалить этот элемент?')) return;
    
    const elementIndex = captainElements.findIndex(el => el.id === elementId);
    if (elementIndex === -1) return;
    
    // Удаление элемента
    captainElements.splice(elementIndex, 1);
    localStorage.setItem('captainElements', JSON.stringify(captainElements));
    
    // Удаление кнопок элемента
    const elementButtons = JSON.parse(localStorage.getItem('captainElementButtons')) || [];
    const filteredButtons = elementButtons.filter(btn => btn.element_id !== elementId);
    localStorage.setItem('captainElementButtons', JSON.stringify(filteredButtons));
    
    loadCaptainElements();
    showNotification('Элемент удален', 'success');
}

function addButtonRow(url = '') {
    const container = document.getElementById('buttonsContainer');
    const buttonNumber = container.children.length + 1;
    
    const buttonRow = document.createElement('div');
    buttonRow.className = 'button-row';
    buttonRow.innerHTML = `
        <span class="button-number">${buttonNumber}</span>
        <input type="url" class="form-input button-url" placeholder="Введите ссылку" value="${url}" style="flex: 1; margin: 0;">
        <button type="button" class="action-btn reject-btn" onclick="removeButtonRow(this)" style="padding: 8px 12px;">×</button>
    `;
    
    container.appendChild(buttonRow);
    updateButtonNumbers();
}

function removeButtonRow(button) {
    button.parentElement.remove();
    updateButtonNumbers();
}

function updateButtonNumbers() {
    const container = document.getElementById('buttonsContainer');
    const buttonRows = container.querySelectorAll('.button-row');
    
    buttonRows.forEach((row, index) => {
        const numberSpan = row.querySelector('.button-number');
        if (numberSpan) {
            numberSpan.textContent = index + 1;
        }
    });
}

function clearButtonsContainer() {
    const container = document.getElementById('buttonsContainer');
    container.innerHTML = '';
}

function clearCaptainElementForm() {
    document.getElementById('elementTitle').value = '';
    document.getElementById('elementImageUrl').value = '';
    document.getElementById('elementSortOrder').value = '0';
    document.getElementById('elementStatus').value = '1';
    clearButtonsContainer();
}

function saveCaptainElement() {
    const title = document.getElementById('elementTitle').value.trim();
    const imageUrl = document.getElementById('elementImageUrl').value.trim();
    const sortOrder = parseInt(document.getElementById('elementSortOrder').value) || 0;
    const status = document.getElementById('elementStatus').value === '1';
    
    if (!title) {
        showNotification('Введите название элемента', 'error');
        return;
    }
    
    // Собрать кнопки
    const buttonRows = document.querySelectorAll('.button-row');
    const buttons = [];
    
    buttonRows.forEach((row, index) => {
        const urlInput = row.querySelector('.button-url');
        const url = urlInput.value.trim();
        
        if (url) {
            buttons.push({
                button_number: index + 1,
                button_url: url
            });
        }
    });
    
    const elementData = {
        title: title,
        image_url: imageUrl,
        sort_order: sortOrder,
        status: status,
        button_count: buttons.length
    };
    
    if (currentCaptainElement) {
        // Редактирование существующего элемента
        elementData.id = currentCaptainElement.id;
        const elementIndex = captainElements.findIndex(el => el.id === currentCaptainElement.id);
        if (elementIndex !== -1) {
            captainElements[elementIndex] = elementData;
        }
    } else {
        // Добавление нового элемента
        elementData.id = Date.now();
        captainElements.push(elementData);
    }
    
    // Сохранение элементов
    localStorage.setItem('captainElements', JSON.stringify(captainElements));
    
    // Сохранение кнопок элемента
    const elementButtons = JSON.parse(localStorage.getItem('captainElementButtons')) || [];
    
    // Удаление старых кнопок этого элемента
    const filteredButtons = elementButtons.filter(btn => btn.element_id !== elementData.id);
    
    // Добавление новых кнопок
    buttons.forEach(button => {
        filteredButtons.push({
            element_id: elementData.id,
            button_number: button.button_number,
            button_url: button.button_url
        });
    });
    
    localStorage.setItem('captainElementButtons', JSON.stringify(filteredButtons));
    
    closeCaptainElementModal();
    loadCaptainElements();
    showNotification(currentCaptainElement ? 'Элемент обновлен' : 'Элемент добавлен', 'success');
}

function closeCaptainElementModal() {
    document.getElementById('captainElementModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    currentCaptainElement = null;
}

function refreshCaptainElements() {
    const refreshBtn = document.getElementById('refreshElementsText');
    if (refreshBtn) {
        refreshBtn.innerHTML = '<span class="loading"></span>';
        
        setTimeout(() => {
            loadCaptainElements();
            refreshBtn.textContent = 'Обновить';
        }, 1000);
    }
}

// ОСТАЛЬНЫЕ ФУНКЦИИ (БЕЗ ИЗМЕНЕНИЙ)

function loadApplications() {
    applications = JSON.parse(localStorage.getItem('applications')) || [];
    const tableBody = document.getElementById('applicationsTableBody');
    
    if (applications.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="no-data">Нет заявок для обработки</td>
            </tr>
        `;
        return;
    }
    
    tableBody.innerHTML = applications.map(app => `
        <tr>
            <td><strong>${app.login}</strong></td>
            <td>${app.server}</td>
            <td>${app.discord}</td>
            <td>${new Date(app.timestamp).toLocaleDateString('ru-RU')}</td>
            <td><span class="status-badge ${app.status}">${getStatusText(app.status)}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn view-btn" onclick="viewApplication('${app.login}')">Просмотр</button>
                    ${app.status === 'pending' ? `
                        <button class="action-btn approve-btn" onclick="approveApplication('${app.login}')">Одобрить</button>
                        <button class="action-btn reject-btn" onclick="rejectApplication('${app.login}')">Отклонить</button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

function loadUsers() {
    users = JSON.parse(localStorage.getItem('users')) || [];
    const tableBody = document.getElementById('usersTableBody');
    
    if (users.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="no-data">Нет зарегистрированных пользователей</td>
            </tr>
        `;
        return;
    }
    
    tableBody.innerHTML = users.map(user => `
        <tr>
            <td><strong>${user.login}</strong></td>
            <td>${user.server}</td>
            <td>${user.discord}</td>
            <td>${user.static}</td>
            <td>${new Date(user.timestamp).toLocaleDateString('ru-RU')}</td>
            <td><span class="status-badge approved">Активен</span></td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn view-btn" onclick="viewUser('${user.login}')">Просмотр</button>
                    <button class="action-btn reject-btn" onclick="deleteUser('${user.login}')">Удалить</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function loadCaptainReports() {
    captainReports = JSON.parse(localStorage.getItem('captainReports')) || [];
    const tableBody = document.getElementById('captainReportsTableBody');
    
    if (!tableBody) return;
    
    if (captainReports.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="no-data">Нет отчетов о каптах</td>
            </tr>
        `;
        return;
    }
    
    tableBody.innerHTML = captainReports.map(report => `
        <tr>
            <td><strong>${report.userId}</strong></td>
            <td>${report.captainName}</td>
            <td>${parseInt(report.damage).toLocaleString()}</td>
            <td>
                <a href="${report.rollbackUrl}" target="_blank" class="app-url">
                    Посмотреть откат
                </a>
            </td>
            <td>${new Date(report.timestamp).toLocaleDateString('ru-RU')}</td>
            <td><span class="status-badge ${report.status}">${getStatusText(report.status)}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn view-btn" onclick="viewCaptainReport(${report.id})">Просмотр</button>
                    ${report.status === 'pending' ? `
                        <button class="action-btn approve-btn" onclick="approveCaptainReport(${report.id})">Одобрить</button>
                        <button class="action-btn reject-btn" onclick="rejectCaptainReport(${report.id})">Отклонить</button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).reverse().join('');
}

function viewCaptainReport(reportId) {
    const report = captainReports.find(r => r.id === reportId);
    if (!report) return;
    
    currentCaptainReport = report;
    
    const detailsHtml = `
        <div class="detail-group">
            <div class="detail-label">Пользователь</div>
            <div class="detail-value">${report.userId}</div>
        </div>
        <div class="detail-group">
            <div class="detail-label">Название капта</div>
            <div class="detail-value">${report.captainName}</div>
        </div>
        <div class="detail-group">
            <div class="detail-label">Урон на капте</div>
            <div class="detail-value">${parseInt(report.damage).toLocaleString()}</div>
        </div>
        <div class="detail-group">
            <div class="detail-label">Ссылка на откат</div>
            <div class="detail-value">
                <a href="${report.rollbackUrl}" target="_blank" class="app-url">${report.rollbackUrl}</a>
            </div>
        </div>
        <div class="detail-group">
            <div class="detail-label">Дата отправки</div>
            <div class="detail-value">${new Date(report.timestamp).toLocaleString('ru-RU')}</div>
        </div>
        <div class="detail-group">
            <div class="detail-label">Статус</div>
            <div class="detail-value"><span class="status-badge ${report.status}">${getStatusText(report.status)}</span></div>
        </div>
    `;
    
    document.getElementById('captainReportDetails').innerHTML = detailsHtml;
    document.getElementById('captainReportModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function approveCaptainReport(reportId) {
    if (!confirm(`Одобрить отчет о капте?`)) return;
    
    const reportIndex = captainReports.findIndex(report => report.id === reportId);
    if (reportIndex === -1) return;
    
    captainReports[reportIndex].status = 'approved';
    localStorage.setItem('captainReports', JSON.stringify(captainReports));
    
    loadStatistics();
    loadCaptainReports();
    showNotification(`Отчет о капте одобрен`, 'success');
}

function rejectCaptainReport(reportId) {
    if (!confirm(`Отклонить отчет о капте?`)) return;
    
    const reportIndex = captainReports.findIndex(report => report.id === reportId);
    if (reportIndex === -1) return;
    
    captainReports[reportIndex].status = 'rejected';
    localStorage.setItem('captainReports', JSON.stringify(captainReports));
    
    loadStatistics();
    loadCaptainReports();
    showNotification(`Отчет о капте отклонен`, 'error');
}

function approveCaptainFromModal() {
    if (currentCaptainReport) {
        closeCaptainModal();
        approveCaptainReport(currentCaptainReport.id);
    }
}

function rejectCaptainFromModal() {
    if (currentCaptainReport) {
        closeCaptainModal();
        rejectCaptainReport(currentCaptainReport.id);
    }
}

function closeCaptainModal() {
    document.getElementById('captainReportModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    currentCaptainReport = null;
}

function refreshCaptainReports() {
    const refreshBtn = document.getElementById('refreshCaptainText');
    refreshBtn.innerHTML = '<span class="loading"></span>';
    
    setTimeout(() => {
        loadCaptainReports();
        loadStatistics();
        refreshBtn.textContent = 'Обновить';
    }, 1000);
}

function loadServerData() {
    const servers = [
        { name: 'Phoenix-01', location: 'Phoenix', status: 'online', cpu: '23%', ram: '67%', users: 45 },
        { name: 'Seattle-01', location: 'Seattle', status: 'online', cpu: '31%', ram: '52%', users: 32 },
        { name: 'Houston-01', location: 'Houston', status: 'maintenance', cpu: '0%', ram: '0%', users: 0 },
        { name: 'Chicago-01', location: 'Chicago', status: 'online', cpu: '18%', ram: '74%', users: 67 },
        { name: 'NewYork-01', location: 'New York', status: 'online', cpu: '45%', ram: '81%', users: 89 },
        { name: 'Boston-01', location: 'Boston', status: 'online', cpu: '28%', ram: '65%', users: 54 },
        { name: 'LA-01', location: 'Los Angeles', status: 'online', cpu: '35%', ram: '70%', users: 76 }
    ];

    const detailedServersTableBody = document.getElementById('detailedServersTable');
    if (detailedServersTableBody) {
        detailedServersTableBody.innerHTML = servers.map(server => `
            <tr>
                <td>${server.name}</td>
                <td>${server.location}</td>
                <td><span class="status-badge ${server.status}">${getStatusText(server.status)}</span></td>
                <td>${server.cpu}</td>
                <td>${server.ram}</td>
                <td>${server.users}</td>
                <td>
                    <button class="action-btn" style="padding: 5px 10px; font-size: 10px;">
                        ${server.status === 'online' ? 'Перезагрузить' : 'Запустить'}
                    </button>
                </td>
            </tr>
        `).join('');
    }
}

function generateLogs() {
    const logs = [
        `[${new Date().toISOString()}] INFO: Система инициализирована`,
        `[${new Date(Date.now() - 60000).toISOString()}] INFO: Администратор вошел в систему`,
        `[${new Date(Date.now() - 120000).toISOString()}] INFO: Сервер Phoenix-01 перезапущен`,
        `[${new Date(Date.now() - 180000).toISOString()}] WARN: Высокая нагрузка на сервер Chicago-01`,
        `[${new Date(Date.now() - 240000).toISOString()}] INFO: Заявка пользователя user123 одобрена`,
        `[${new Date(Date.now() - 300000).toISOString()}] INFO: Резервное копирование завершено`,
        `[${new Date(Date.now() - 360000).toISOString()}] INFO: Обновление безопасности установлено`,
        `[${new Date(Date.now() - 420000).toISOString()}] ERROR: Временная недоступность Houston-01`,
        `[${new Date(Date.now() - 480000).toISOString()}] INFO: Система мониторинга активирована`,
        `[${new Date(Date.now() - 540000).toISOString()}] WARN: Попытка несанкционированного доступа заблокирована`,
        `[${new Date(Date.now() - 600000).toISOString()}] INFO: Пользователь newuser зарегистрирован`,
        `[${new Date(Date.now() - 660000).toISOString()}] INFO: Настройки системы обновлены`
    ];

    const logsContainer = document.getElementById('logsContainer');
    if (logsContainer) {
        logsContainer.innerHTML = logs.join('<br>');
    }
}

function loadSettings() {
    document.getElementById('autoApproval').value = adminSettings.autoApproval;
    document.getElementById('dailyLimit').value = adminSettings.dailyLimit;
    document.getElementById('emailNotifications').value = adminSettings.emailNotifications;
    document.getElementById('welcomeMessage').value = adminSettings.welcomeMessage;
    document.getElementById('systemAnnouncements').value = adminSettings.systemAnnouncements;
    document.getElementById('maintenanceMode').value = adminSettings.maintenanceMode;
    document.getElementById('maxUsers').value = adminSettings.maxUsers;
}

function getStatusText(status) {
    switch(status) {
        case 'pending': return 'На рассмотрении';
        case 'approved': return 'Одобрено';
        case 'rejected': return 'Отклонено';
        case 'online': return 'Онлайн';
        case 'offline': return 'Офлайн';
        case 'maintenance': return 'Техработы';
        default: return 'Неизвестно';
    }
}

function viewApplication(login) {
    const app = applications.find(a => a.login === login);
    if (!app) return;
    
    currentApplication = app;
    
    const detailsHtml = `
        <div class="detail-group">
            <div class="detail-label">Логин</div>
            <div class="detail-value">${app.login}</div>
        </div>
        <div class="detail-group">
            <div class="detail-label">Сервер</div>
            <div class="detail-value">${app.server}</div>
        </div>
        <div class="detail-group">
            <div class="detail-label">Статик на сервере</div>
            <div class="detail-value">${app.static}</div>
        </div>
        <div class="detail-group">
            <div class="detail-label">Discord</div>
            <div class="detail-value">${app.discord}</div>
        </div>
        <div class="detail-group">
            <div class="detail-label">Откаты с ДД</div>
            <div class="detail-value">
                <a href="${app.url1}" target="_blank" class="app-url">${app.url1}</a><br>
                <a href="${app.url2}" target="_blank" class="app-url">${app.url2}</a>
            </div>
        </div>
        <div class="detail-group">
            <div class="detail-label">О себе</div>
            <div class="detail-value">${app.about}</div>
        </div>
        <div class="detail-group">
            <div class="detail-label">Дата подачи</div>
            <div class="detail-value">${new Date(app.timestamp).toLocaleString('ru-RU')}</div>
        </div>
        <div class="detail-group">
            <div class="detail-label">Статус</div>
            <div class="detail-value"><span class="status-badge ${app.status}">${getStatusText(app.status)}</span></div>
        </div>
    `;
    
    document.getElementById('applicationDetails').innerHTML = detailsHtml;
    document.getElementById('applicationModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function viewUser(login) {
    const user = users.find(u => u.login === login);
    if (!user) return;
    
    // Используем ту же модальность для отображения пользователя
    currentApplication = user;
    viewApplication(login);
}

function approveApplication(login) {
    if (!confirm(`Одобрить заявку пользователя ${login}?`)) return;
    
    const appIndex = applications.findIndex(app => app.login === login);
    if (appIndex === -1) return;
    
    const application = applications[appIndex];
    application.status = 'approved';
    
    // Перемещение в пользователи
    users.push(application);
    applications.splice(appIndex, 1);
    
    // Сохранение в localStorage
    localStorage.setItem('applications', JSON.stringify(applications));
    localStorage.setItem('users', JSON.stringify(users));
    
    // Обновление текущего пользователя если он авторизован
    const currentUser = JSON.parse(localStorage.getItem('currentUser'));
    if (currentUser && currentUser.login === login) {
        currentUser.status = 'approved';
        localStorage.setItem('currentUser', JSON.stringify(currentUser));
    }
    
    loadStatistics();
    loadApplications();
    showNotification(`Заявка пользователя ${login} одобрена`, 'success');
}

function rejectApplication(login) {
    if (!confirm(`Отклонить заявку пользователя ${login}?`)) return;
    
    const appIndex = applications.findIndex(app => app.login === login);
    if (appIndex === -1) return;
    
    applications[appIndex].status = 'rejected';
    
    // Удаление заявки через некоторое время
    setTimeout(() => {
        const index = applications.findIndex(app => app.login === login);
        if (index !== -1) {
            applications.splice(index, 1);
            localStorage.setItem('applications', JSON.stringify(applications));
        }
    }, 5000);
    
    localStorage.setItem('applications', JSON.stringify(applications));
    
    // Удаление текущего пользователя если он авторизован
    const currentUser = JSON.parse(localStorage.getItem('currentUser'));
    if (currentUser && currentUser.login === login) {
        localStorage.removeItem('currentUser');
    }
    
    loadStatistics();
    loadApplications();
    showNotification(`Заявка пользователя ${login} отклонена`, 'error');
}

function deleteUser(login) {
    if (!confirm(`Удалить пользователя ${login}?`)) return;
    
    const userIndex = users.findIndex(user => user.login === login);
    if (userIndex === -1) return;
    
    users.splice(userIndex, 1);
    localStorage.setItem('users', JSON.stringify(users));
    
    // Удаление текущего пользователя если он авторизован
    const currentUser = JSON.parse(localStorage.getItem('currentUser'));
    if (currentUser && currentUser.login === login) {
        localStorage.removeItem('currentUser');
    }
    
    loadStatistics();
    loadUsers();
    showNotification(`Пользователь ${login} удален`, 'error');
}

function approveFromModal() {
    if (currentApplication) {
        closeModal();
        approveApplication(currentApplication.login);
    }
}

function rejectFromModal() {
    if (currentApplication) {
        closeModal();
        rejectApplication(currentApplication.login);
    }
}

function refreshData() {
    const refreshBtn = document.getElementById('refreshBtn');
    refreshBtn.innerHTML = '<span class="loading"></span> Обновление...';
    
    setTimeout(() => {
        loadStatistics();
        loadApplications();
        loadUsers();
        loadCaptainReports();
        loadCaptainElements();
        loadServerData();
        updateSystemMetrics();
        generateLogs();
        
        refreshBtn.textContent = 'Обновить данные';
        showNotification('Данные обновлены');
    }, 2000);
}

function refreshApplications() {
    const refreshBtn = document.getElementById('refreshText');
    refreshBtn.innerHTML = '<span class="loading"></span>';
    
    setTimeout(() => {
        loadApplications();
        loadStatistics();
        refreshBtn.textContent = 'Обновить';
    }, 1000);
}

function refreshUsers() {
    loadUsers();
    loadStatistics();
}

function saveSettings() {
    adminSettings = {
        autoApproval: document.getElementById('autoApproval').value,
        dailyLimit: parseInt(document.getElementById('dailyLimit').value),
        emailNotifications: document.getElementById('emailNotifications').value,
        welcomeMessage: document.getElementById('welcomeMessage').value,
        systemAnnouncements: document.getElementById('systemAnnouncements').value,
        maintenanceMode: document.getElementById('maintenanceMode').value,
        maxUsers: parseInt(document.getElementById('maxUsers').value)
    };
    
    localStorage.setItem('adminSettings', JSON.stringify(adminSettings));
    showNotification('Настройки сохранены', 'success');
}

function startAutoRefresh() {
    refreshInterval = setInterval(() => {
        updateSystemMetrics();
    }, 30000); // Обновление каждые 30 секунд
}

function closeModal() {
    document.getElementById('applicationModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    currentApplication = null;
}

function logout() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
    
    if (confirm('Выйти из админ-панели?')) {
        localStorage.removeItem('adminAuth');
        window.location.href = 'index.html';
    }
}

function showNotification(message, type = 'success') {
    // Создание и показ уведомления
    const notification = document.createElement('div');
    notification.className = `status-message show ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 40px;
        background: rgba(0, 0, 0, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 20px 30px;
        color: #ffffff;
        z-index: 3000;
        transform: translateX(0);
        max-width: 350px;
        font-size: 14px;
        font-weight: 500;
    `;
    
    if (type === 'error') {
        notification.style.borderColor = '#ff0000';
        notification.style.background = 'rgba(255, 0, 0, 0.1)';
    } else if (type === 'success') {
        notification.style.borderColor = '#00ff00';
        notification.style.background = 'rgba(0, 255, 0, 0.1)';
    }
    
    // Удаление существующих уведомлений
    const existingNotifications = document.querySelectorAll('.status-message');
    existingNotifications.forEach(n => n.remove());
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// Закрытие модального окна при клике вне его
window.addEventListener('click', function(event) {
    const modal = document.getElementById('applicationModal');
    const captainModal = document.getElementById('captainReportModal');
    const elementModal = document.getElementById('captainElementModal');
    
    if (event.target === modal) {
        closeModal();
    }
    if (event.target === captainModal) {
        closeCaptainModal();
    }
    if (event.target === elementModal) {
        closeCaptainElementModal();
    }
});

// Обработка изменения размера окна
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.remove('open');
    }
});

// Предотвращение случайного закрытия
window.addEventListener('beforeunload', function(e) {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});

// Генерация тестовых данных для демонстрации
function generateTestData() {
    if (applications.length === 0 && users.length === 0) {
        const testApplications = [
            {
                id: Date.now(),
                login: 'testuser1',
                password: 'password123',
                server: 'Phoenix',
                static: '50',
                discord: 'testuser1#1234',
                url1: 'https://example.com/1',
                url2: 'https://example.com/2',
                about: 'Опытный игрок, играю уже 5 лет',
                status: 'pending',
                timestamp: new Date().toISOString()
            },
            {
                id: Date.now() + 1,
                login: 'testuser2',
                password: 'password456',
                server: 'Seattle',
                static: '75',
                discord: 'testuser2#5678',
                url1: 'https://example.com/3',
                url2: 'https://example.com/4',
                about: 'Новичок, хочу изучить систему',
                status: 'pending',
                timestamp: new Date(Date.now() - 3600000).toISOString()
            }
        ];
        
        const testUsers = [
            {
                id: Date.now() + 2,
                login: 'approveduser1',
                password: 'password789',
                server: 'Chicago',
                static: '100',
                discord: 'approved1#9999',
                url1: 'https://example.com/5',
                url2: 'https://example.com/6',
                about: 'Активный пользователь системы',
                status: 'approved',
                timestamp: new Date(Date.now() - 86400000).toISOString()
            }
        ];

        // Тестовые отчеты о каптах
        const testCaptainReports = [
            {
                id: Date.now() + 3,
                userId: 'approveduser1',
                captainName: 'Test Captain 1',
                damage: 15000,
                rollbackUrl: 'https://example.com/rollback1',
                status: 'pending',
                timestamp: new Date().toISOString()
            },
            {
                id: Date.now() + 4,
                userId: 'approveduser1',
                captainName: 'Test Captain 2',
                damage: 20000,
                rollbackUrl: 'https://example.com/rollback2',
                status: 'approved',
                timestamp: new Date(Date.now() - 3600000).toISOString()
            }
        ];

        // Тестовые элементы каптов
        const testCaptainElements = [
            {
                id: Date.now() + 5,
                title: 'Боевые корабли',
                image_url: 'https://via.placeholder.com/400x300/1a1a1a/ffffff?text=Боевые+корабли',
                sort_order: 1,
                status: true,
                button_count: 3
            },
            {
                id: Date.now() + 6,
                title: 'Торговые суда',
                image_url: 'https://via.placeholder.com/400x300/1a1a1a/ffffff?text=Торговые+суда',
                sort_order: 2,
                status: true,
                button_count: 2
            }
        ];

        // Тестовые кнопки для элементов
        const testElementButtons = [
            { element_id: Date.now() + 5, button_number: 1, button_url: 'https://example.com/warship1' },
            { element_id: Date.now() + 5, button_number: 2, button_url: 'https://example.com/warship2' },
            { element_id: Date.now() + 5, button_number: 3, button_url: 'https://example.com/warship3' },
            { element_id: Date.now() + 6, button_number: 1, button_url: 'https://example.com/trade1' },
            { element_id: Date.now() + 6, button_number: 2, button_url: 'https://example.com/trade2' }
        ];
        
        localStorage.setItem('applications', JSON.stringify(testApplications));
        localStorage.setItem('users', JSON.stringify(testUsers));
        localStorage.setItem('captainReports', JSON.stringify(testCaptainReports));
        localStorage.setItem('captainElements', JSON.stringify(testCaptainElements));
        localStorage.setItem('captainElementButtons', JSON.stringify(testElementButtons));
        
        applications = testApplications;
        users = testUsers;
        captainReports = testCaptainReports;
        captainElements = testCaptainElements;
        
        loadStatistics();
        loadApplications();
        loadUsers();
        loadCaptainReports();
        loadCaptainElements();
    }
}

// Генерация тестовых данных при первом запуске
setTimeout(generateTestData, 1000);