# Sahayak - Product Overview

## Project Purpose
Sahayak is a hyperlocal service marketplace connecting customers who need help with skilled helpers (workers) in their immediate vicinity. The platform enables real-time discovery of nearby helpers using geolocation, voice-based task posting, and instant task matching within a 5km radius.

## Value Proposition
- **Zero-Cost Infrastructure**: Built entirely with free tools (OpenStreetMap, Web Speech API, shared hosting) to eliminate operational costs
- **Hyperlocal Focus**: Connects users with helpers within 5km radius using Haversine distance calculation
- **Voice-First Interface**: Supports multilingual voice input (Hindi, English, Bengali) for accessibility in diverse markets
- **Dual-Mode Architecture**: Users can switch between seeking help (customer mode) and providing help (helper mode) seamlessly
- **Real-Time Matching**: AJAX short-polling (5-10 second intervals) provides near real-time task updates without WebSocket complexity

## Key Features

### For Customers
- Voice-to-text task posting with automatic skill categorization
- Interactive map showing nearby available helpers with skills and rates
- Real-time helper search within configurable radius (default 5km)
- Task lifecycle management (post, track, complete, rate)
- Integrated wallet system for payments and tips
- Task history and helper ratings

### For Helpers
- Live mode toggle to receive nearby task notifications
- Automatic geolocation tracking when in helper mode
- Task acceptance/rejection workflow
- Earnings tracking and wallet management
- Profile management with skills, rates, and availability status
- Rating and review system

### Core Capabilities
- **Geospatial Matching**: SQL-based Haversine formula for distance calculation
- **Session-Based Mode Switching**: PHP sessions control customer/helper view without page reload
- **Availability Management**: Automatic helper unavailability when switching to customer mode
- **Multi-Language Support**: Voice recognition supports hi-IN, en-IN, bn-IN locales
- **Responsive Design**: Tailwind CSS for mobile-first responsive UI

## Target Users

### Primary Users
- **Customers**: Urban residents needing immediate local services (electricians, plumbers, movers, laborers)
- **Helpers**: Skilled workers seeking flexible gig opportunities in their neighborhood

### Use Cases
1. **Emergency Repairs**: Customer needs electrician for power outage, finds helper within 2km in under 1 minute
2. **Moving Assistance**: Customer posts voice task "need 2 people for furniture moving", system auto-categorizes and shows nearby labor helpers
3. **Flexible Work**: Helper toggles to "live" mode during free hours, receives task notifications from nearby customers
4. **Multi-Role Users**: A plumber can seek help for moving (customer mode) while also offering plumbing services (helper mode)

## Business Model
- Commission-based revenue on completed tasks
- Freemium features (priority listing for helpers, extended search radius for customers)
- Zero infrastructure cost enables sustainable low-commission model
