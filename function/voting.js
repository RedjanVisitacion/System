const candidates = {
  IT: { President: [], "Vice President": [], GenSec: [], AssSec: [], Treasurer: [], Auditor: [], PIO: [] },
  "Food Processing": { President: [], "Vice President": [], GenSec: [], AssSec: [], Treasurer: [], Auditor: [], PIO: [] },
  Education: { President: [], "Vice President": [], GenSec: [], AssSec: [], Treasurer: [], Auditor: [], PIO: [] },
  USG: {
    President: [], "Vice President": [], GenSec: [], AssSec: [], Treasurer: [], Auditor: [], PIO: [], 
    "BTLED Rep": [], "BSIT Rep": [], "BFPT Rep": []
  }
};

const votes = structuredClone(candidates);
const voteTracker = {};
let currentUser = { role: '', department: '', id: '' };

function selectRole(role) {
  // Only allow "student" to select role manually
  if (role === 'student') {
    currentUser = { role: 'student' };
    document.getElementById('departmentContainer').classList.remove('d-none');
  } else {
    currentUser = { role: 'comelec' };
    document.getElementById('departmentContainer').classList.add('d-none');
  }

  document.getElementById('roleSelectionForm').classList.add('d-none');
  document.getElementById('loginForm').classList.remove('d-none');
}









function updatePositions() {
  const department = document.getElementById('candidateDept').value;
  const repOptions = document.querySelectorAll('.rep-option');

  if (department !== 'USG') {
    repOptions.forEach(option => option.classList.add('d-none'));
  } else {
    repOptions.forEach(option => option.classList.remove('d-none'));
  }
}

// Show the voting options for students based on their department
function showStudentVoting() {
  const dept = currentUser.department;
  const voted = voteTracker[currentUser.id] && voteTracker[currentUser.id][dept] ? voteTracker[currentUser.id][dept] : {}; // Track votes by department
  const deptDiv = document.getElementById('deptCandidates');
  const usgDiv = document.getElementById('usgCandidates');
  deptDiv.innerHTML = `<h4>${dept} Department Voting</h4>`;
  usgDiv.innerHTML = `<h4>USG Voting</h4>`;

  for (let pos in candidates[dept]) {
    if (voted[`${dept}-${pos}`]) {
      deptDiv.innerHTML += `<p><strong>${pos}:</strong> Already voted.</p>`;
    } else {
      deptDiv.innerHTML += `<p><strong>${pos}:</strong></p>`;
      candidates[dept][pos].forEach(name => {
        deptDiv.innerHTML += `<button class="btn btn-outline-primary btn-sm m-1" onclick="vote('${dept}', '${pos}', '${name}')">${name}</button>`;
      });
    }
  }

  for (let pos in candidates.USG) {
    if (voted[`USG-${pos}`]) {
      usgDiv.innerHTML += `<p><strong>${pos}:</strong> Already voted.</p>`;
    } else {
      usgDiv.innerHTML += `<p><strong>${pos}:</strong></p>`;
      candidates.USG[pos].forEach(name => {
        usgDiv.innerHTML += `<button class="btn btn-outline-success btn-sm m-1" onclick="vote('USG', '${pos}', '${name}')">${name}</button>`;
      });
    }
  }
}

function vote(group, position, name) {
  const user = currentUser.id;
  const dept = currentUser.department; // We need to track votes by department
  const key = `${group}-${position}`;

  // Initialize voteTracker for the department if not yet initialized
  if (!voteTracker[user]) {
    voteTracker[user] = {};
  }
  if (!voteTracker[user][dept]) {
    voteTracker[user][dept] = {}; // Track votes by department
  }

  // If the user has already voted for this position in this department, prevent voting
  if (voteTracker[user][dept][key]) {
    alert("You have already voted for this position in this department.");
    return;
  }

  // Increment the vote for the selected candidate
  votes[group][position][name] = (votes[group][position][name] || 0) + 1;

  // Mark this user as having voted for this position in this department
  voteTracker[user][dept][key] = true;

  alert(`You voted for ${name} as ${position} in ${group}.`);
  showStudentVoting(); // Update the voting interface
}


function addCandidate() {
  const name = document.getElementById('newCandidate').value.trim();
  const dept = document.getElementById('candidateDept').value;
  const pos = document.getElementById('candidatePosition').value;

  if (!name || !dept || !pos) {
    alert("Please fill in all candidate details.");
    return;
  }

  candidates[dept][pos].push(name);
  alert(`${name} added to ${pos} in ${dept}.`);
  showAllCandidates();
  showResults();
}

function removeCandidate(department, position, name) {
  const index = candidates[department][position].indexOf(name);
  if (index !== -1) {
    candidates[department][position].splice(index, 1);
    alert(`${name} removed from ${position} in ${department}.`);
    showAllCandidates();
    showResults();
  }
}

function showAllCandidates() {
  const div = document.getElementById('allCandidates');
  div.innerHTML = '';
  for (let dept in candidates) {
    div.innerHTML += `<h5>${dept}</h5>`;
    for (let pos in candidates[dept]) {
      const list = candidates[dept][pos].map(n => {
        return `<li>${n} <button class="btn btn-danger btn-sm ms-2" onclick="removeCandidate('${dept}', '${pos}', '${n}')">Remove</button></li>`;
      }).join('');
      div.innerHTML += `<strong>${pos}:</strong><ul>${list}</ul>`;
    }
  }
}

function showResults() {
  let output = '';
  for (let dept in votes) {
    output += `${dept}:\n`;
    for (let pos in votes[dept]) {
      output += `  ${pos}:\n`;
      for (let name in votes[dept][pos]) {
        output += `    ${name}: ${votes[dept][pos][name]} votes\n`;
      }
    }
    output += '\n';
  }
  document.getElementById('results').textContent = output;
}

function logout() {
  currentUser = { role: '', department: '', id: '' };
  document.getElementById('roleSelectionForm').classList.remove('d-none');
  document.getElementById('studentSection').classList.add('d-none');
  document.getElementById('comelecSection').classList.add('d-none');
  document.getElementById('id').value = '';
  document.getElementById('password').value = '';
  document.getElementById('department').value = '';
}



 // Login function
 function login() {
  const id = document.getElementById('id').value.trim();
  const password = document.getElementById('password').value.trim();
  const department = document.getElementById('department')?.value;

  if (!id || !password) {
    alert("Please enter your ID and password.");
    return;
  }

  const storedUser = JSON.parse(localStorage.getItem(id));

  if (!storedUser) {
    alert("User not found.");
    return;
  }

  if (storedUser.password !== password) {
    alert("Incorrect password.");
    return;
  }

  // If the user is a student, department must be selected
  if (storedUser.role === 'student' && !department) {
    alert("Please select your department.");
    return;
  }

  // Set currentUser based on the stored user and override department if needed
  currentUser = storedUser;
  currentUser.id = id;  // Save the user ID for future reference (password change, etc.)

  if (currentUser.role === 'student') {
    currentUser.department = department;  // Department is only set for students
  }

  voteTracker[id] = voteTracker[id] || {};
  document.getElementById('loginForm').classList.add('d-none');

  // Show the correct section based on role
  if (currentUser.role === 'student') {
    document.getElementById('studentSection').classList.remove('d-none');
    showStudentVoting();
  } else if (currentUser.role === 'comelec') {
    document.getElementById('comelecSection').classList.remove('d-none');
    showAllCandidates();
    showResults();
  }
}

// Handle role selection - only students should select department
function selectRole(role) {
  currentUser.role = role;
  document.getElementById('roleSelectionForm').classList.add('d-none');
  document.getElementById('loginForm').classList.remove('d-none');

  if (role === 'comelec') {
    document.getElementById('department').classList.add('d-none'); // Hide department for COMELEC
  } else {
    document.getElementById('department').classList.remove('d-none'); // Show department for students
  }
}



// Show the change password form
function showChangePasswordForm() {
  document.getElementById('changePasswordForm').classList.remove('d-none');
  document.getElementById('studentSection').classList.add('d-none');
  document.getElementById('comelecSection').classList.add('d-none');
}

// Cancel the password change and go back to user's section
function cancelPasswordChange() {
  document.getElementById('changePasswordForm').classList.add('d-none');
  
  if (currentUser && currentUser.role === 'student') {
    document.getElementById('studentSection').classList.remove('d-none');
  } else if (currentUser && currentUser.role === 'comelec') {
    document.getElementById('comelecSection').classList.remove('d-none');
  }
}

// Handle changing the password for the current logged-in user only
function changePassword() {
  const currentPassword = document.getElementById('currentPassword').value.trim();
  const newPassword = document.getElementById('newPassword').value.trim();
  const confirmPassword = document.getElementById('confirmPassword').value.trim();

  if (!currentUser || !currentUser.id) {
    alert("No user is currently logged in.");
    return;
  }

  const userData = JSON.parse(localStorage.getItem(currentUser.id));

  if (!userData || !userData.password) {
    alert("No password found for this user.");
    return;
  }

  if (currentPassword !== userData.password) {
    alert("Current password is incorrect.");
    return;
  }

  if (newPassword !== confirmPassword) {
    alert("New passwords do not match.");
    return;
  }

  // Update password in localStorage
  userData.password = newPassword;
  localStorage.setItem(currentUser.id, JSON.stringify(userData));
  currentUser.password = newPassword;

  alert("Password changed successfully!");

  // Hide form and show section again
  document.getElementById('changePasswordForm').classList.add('d-none');

  if (currentUser.role === 'student') {
    document.getElementById('studentSection').classList.remove('d-none');
    showStudentVoting(); // Assuming this function handles what should happen after login
  } else if (currentUser.role === 'comelec') {
    document.getElementById('comelecSection').classList.remove('d-none');
    showAllCandidates(); // Assuming this function handles what should happen for COMELEC
    showResults();
  }
}



// Create student account
const studentUser = {
  id: "20231234",
  password: "1234",
  role: "student",
  department: "BSIT"
};
localStorage.setItem(studentUser.id, JSON.stringify(studentUser));

// Create COMELEC account
const comelecUser = {
  id: "comelec001",
  password: "admin123",
  role: "comelec"
};
localStorage.setItem(comelecUser.id, JSON.stringify(comelecUser));
