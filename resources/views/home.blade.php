<!DOCTYPE html>
<html>
<head>
    <title>Financial Year Finder</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f9fafb;
            margin: 0;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            min-height: 100vh;
            color: #333;
        }

        .container {
            background: #fff;
            max-width: 800px;
            width: 100%;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        h1 {
            font-size: 2.2rem;
            font-weight: 700;
            text-align: center;
            color: #222;
            margin-bottom: 30px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #444;
        }

        select {
            width: 100%;
            padding: 12px 15px;
            font-size: 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }
        select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 8px rgba(59, 130, 246, 0.5);
            outline: none;
        }

        button {
            display: block;
            margin: 30px auto 0;
            background-color: #3b82f6;
            color: white;
            font-weight: 700;
            border: none;
            padding: 14px 36px;
            font-size: 1.1rem;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            box-shadow: 0 4px 12px rgba(59,130,246,0.4);
        }
        button:hover {
            background-color: #2563eb;
        }
        button:focus {
            outline: 3px solid #93c5fd;
            outline-offset: 2px;
        }

        .result {
            margin-top: 35px;
            padding: 25px 30px;
            background-color: #f0f4ff;
            border-radius: 12px;
            box-shadow: inset 0 0 8px rgba(59, 130, 246, 0.2);
            min-height: 160px;
            color: #1e293b;
            font-size: 1rem;
            line-height: 1.5;
        }

        .result h3 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: #1e40af;
        }

        .result h4 {
            margin-top: 20px;
            font-weight: 700;
            font-size: 1.2rem;
            color: #2563eb;
            margin-bottom: 10px;
        }

        .result ul {
            list-style-type: disc;
            padding-left: 20px;
        }

        .result li {
            margin-bottom: 6px;
        }

        .error-message {
            color: #dc2626;
            font-weight: 600;
            margin-top: 10px;
        }

         .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #3b82f6;
            border-top: 4px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            position: absolute;
            top: 50%;
            left: 50%;
            margin-top: -20px;
            margin-left: -20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Financial Year & Public Holidays</h1>

        <label for="country">Select Country:</label>
        <select id="country" onchange="populateYears()">
            <option value="">-- Select Country --</option>
            @foreach ($countries as $key => $value)
                <option value="{{ $key }}">{{ $value }}</option>
            @endforeach
        </select>

        <label for="year">Select Year:</label>
        <select id="year">
            <option value="">-- Select Year --</option>
        </select>

        <button onclick="fetchFinancialData()">Get Financial Year Details</button>

        <div class="result" id="result">No Results</div>
    </div>

    <script>
        function showLoading() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<div class="spinner"></div>';
        }

        // Get years list
        function populateYears() {
            const country = document.getElementById('country').value;
            const yearSelect = document.getElementById('year');
            const resultDiv = document.getElementById('result');

            resultDiv.innerHTML = 'No Results';
            yearSelect.innerHTML = '<option value="">-- Select Year --</option>';

            if (!country) {
                resultDiv.innerHTML = '<p class="error-message">Please select a country first.</p>';
                return;
            }

            showLoading();

            fetch(`/get-years?country=${encodeURIComponent(country)}`)
                .then(async response => {
                    const data = await response.json();
                    if (!response.ok) {
                        if (response.status === 422 && data.errors) {
                            const messages = Object.values(data.errors)
                                .flat()
                                .map(msg => `<p class="error-message">${msg}</p>`)
                                .join('');
                            resultDiv.innerHTML = messages;
                        } else {
                            resultDiv.innerHTML = `<p class="error-message">Error: ${data.message || response.statusText}</p>`;
                        }
                        return;
                    }
                    let years = data.data
                    years.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.value;
                        option.textContent = item.label;
                        yearSelect.appendChild(option);
                    });
                    resultDiv.innerHTML = 'No Results';
                })
                .catch(err => {
                    console.error(err);
                    resultDiv.innerHTML = '<p class="error-message">Failed to fetch years. Please try again.</p>';
                });
        }

        // Get financial year information and holidays list
        function fetchFinancialData() {
            const country = document.getElementById('country').value;
            const year = document.getElementById('year').value;
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = 'No Results';

            if (!country) {
                resultDiv.innerHTML = '<p class="error-message">Please select a country.</p>';
                return;
            }
            if (!year) {
                resultDiv.innerHTML = '<p class="error-message">Please select a year.</p>';
                return;
            }

            showLoading();

            fetch('/financial-data', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ country, year })
            })
            .then(async response => {
                const data = await response.json();

                if (!response.ok) {
                    if (response.status === 422 && data.errors) {
                        const messages = Object.values(data.errors)
                            .flat()
                            .map(msg => `<p class="error-message">${msg}</p>`)
                            .join('');
                        resultDiv.innerHTML = messages;
                    } else {
                        resultDiv.innerHTML = `<p class="error-message">Error: ${data.message || response.statusText}</p>`;
                    }
                    return;
                }

                const result = data.data;

                let html = `<h3>Financial Year (${country.toUpperCase()})</h3>`;
                html += `<p><strong>Start Date:</strong> ${result.start_date}</p>`;
                html += `<p><strong>End Date:</strong> ${result.end_date}</p>`;

                if (result.holidays.length > 0) {
                    html += '<h4>Public Holidays (Excluding Weekends)</h4><ul>';
                    result.holidays.forEach(h => {
                        html += `<li>${h.date}: ${h.name}</li>`;
                    });
                    html += '</ul>';
                } else {
                    html += '<p>No holidays found in this period.</p>';
                }

                resultDiv.innerHTML = html;
            })
            .catch(error => {
                console.error(error);
                resultDiv.innerHTML = '<p class="error-message">An unexpected error occurred.</p>';
            });
        }
    </script>
</body>
</html>
